<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierPaymentRequest;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\SupplierBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SupplierPaymentController extends Controller
{
    protected $supplierBalanceService;

    public function __construct(SupplierBalanceService $supplierBalanceService)
    {
        $this->supplierBalanceService = $supplierBalanceService;
    }

    public function index(Request $request, string $supplierId)
    {
        if (!$request->user()->hasPermissionTo('view suppliers')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();
        $supplier = Supplier::where('business_id', $businessId)->findOrFail($supplierId);

        $payments = SupplierPayment::with('user')
            ->where('business_id', $businessId)
            ->where('supplier_id', $supplier->id)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($payments);
    }

    public function store(StoreSupplierPaymentRequest $request, string $supplierId)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('record supplier payments')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();
        $supplier = Supplier::where('business_id', $businessId)->findOrFail($supplierId);

        $amount = (float) $request->amount;

        // Resolve Financial Account for supplier payment
        $targetAccount = null;
        if (!empty($request->financial_account_id)) {
            $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                ->where('id', $request->financial_account_id)
                ->where('status', 'active')
                ->first();
            if (!$targetAccount) {
                return response()->json(['error' => 'Selected financial account is invalid or inactive.'], 422);
            }
        } else {
            $isCash = strtolower(trim($request->payment_method ?? 'cash')) === 'cash';
            if ($isCash && $user->branch_id) {
                $targetAccount = app(\App\Services\FinancialAccountService::class)->getDefaultCashAccount($businessId, $user->branch_id);
            } else {
                $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                    ->where('type', 'bank')
                    ->where('status', 'active')
                    ->first();
            }
        }

        if (!$targetAccount || $targetAccount->status !== 'active') {
            return response()->json(['error' => 'No active financial account available for supplier payment outflow.'], 422);
        }

        $idempotencyKey = $request->idempotency_key;
        if ($idempotencyKey) {
            $existing = SupplierPayment::where('business_id', $businessId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                // Check if material parameters match
                $sameSupplier = (int) $existing->supplier_id === (int) $supplier->id;
                $sameAmount = abs((float) $existing->amount - $amount) < 0.001;
                $sameMethod = $existing->payment_method === ($request->payment_method ?? 'cash');
                $reqDate = $request->date ? \Carbon\Carbon::parse($request->date)->toDateString() : date('Y-m-d');
                $existingDate = $existing->date ? \Carbon\Carbon::parse($existing->date)->toDateString() : null;
                $sameDate = ($existingDate === $reqDate);

                if ($sameSupplier && $sameAmount && $sameMethod && $sameDate) {
                    return response()->json($existing->load('supplier'), 200);
                }

                return response()->json([
                    'message' => 'Idempotency key has already been used with different payment parameters.',
                    'errors' => [
                        'idempotency_key' => ['Idempotency key has already been used with different payment parameters.']
                    ]
                ], 422);
            }
        }

        try {
            return DB::transaction(function () use ($request, $user, $businessId, $supplier, $amount, $targetAccount, $idempotencyKey) {
                // 1. Record balance deduction atomically (validates overpayment)
                $this->supplierBalanceService->recordPayment($supplier, $amount);

                // 2. Create SupplierPayment record
                $payment = SupplierPayment::create([
                    'business_id' => $businessId,
                    'supplier_id' => $supplier->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'date' => $request->date ?? now()->toDateString(),
                    'payment_method' => $request->payment_method ?? 'cash',
                    'notes' => $request->notes,
                    'idempotency_key' => $idempotencyKey,
                ]);

                // 3. Post physical outflow account_movement (validates sufficient account balance)
                app(\App\Services\AccountMovementService::class)->postOutflow($targetAccount, [
                    'branch_id' => $user->branch_id ?? $targetAccount->branch_id,
                    'movement_category' => 'supplier_payment',
                    'reference_type' => SupplierPayment::class,
                    'reference_id' => $payment->id,
                    'amount' => $amount,
                    'date' => $payment->date,
                    'description' => "Supplier Payment to {$supplier->name}",
                    'user_id' => $user->id,
                    'idempotency_key' => $idempotencyKey ? "sp_{$idempotencyKey}" : null,
                ]);

                app(\App\Services\AuditService::class)->log(
                    logName: 'supplier',
                    event: 'created',
                    description: "Supplier payment of {$amount} recorded for {$supplier->name}",
                    subject: $payment,
                    properties: [
                        'action' => 'supplier_payment',
                        'payment_id' => $payment->id,
                        'supplier_id' => $supplier->id,
                        'amount' => $amount,
                        'payment_method' => $payment->payment_method,
                        'financial_account_id' => $targetAccount->id,
                    ],
                    branchId: $user->branch_id ?? $targetAccount->branch_id,
                    businessId: (int) $businessId
                );

                return response()->json($payment->load('supplier'), 201);
            });
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
