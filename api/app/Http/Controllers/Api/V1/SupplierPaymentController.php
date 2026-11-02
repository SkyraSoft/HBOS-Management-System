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

    public function allPayments(Request $request)
    {
        if (!$request->user()->hasPermissionTo('view suppliers')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();

        $query = SupplierPayment::with(['supplier', 'purchase', 'user'])
            ->where('business_id', $businessId);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $payments = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(100)
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

        // Optional purchase allocation check
        $purchase = null;
        if (!empty($request->purchase_id)) {
            $purchase = \App\Models\Purchase::where('business_id', $businessId)
                ->where('supplier_id', $supplier->id)
                ->where('id', $request->purchase_id)
                ->first();
            if (!$purchase) {
                return response()->json(['error' => 'Selected purchase order is invalid or does not belong to this supplier.'], 422);
            }
            if (bccomp((string) $amount, (string) $purchase->due_amount, 2) > 0) {
                return response()->json(['error' => "Payment amount ({$amount}) exceeds purchase outstanding due amount ({$purchase->due_amount})."], 422);
            }
        }

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
            if ($user->branch_id && $targetAccount->branch_id && (int) $user->branch_id !== (int) $targetAccount->branch_id) {
                return response()->json(['error' => 'Selected financial account does not belong to your branch.'], 422);
            }
        } else {
            $isCash = strtolower(trim($request->payment_method ?? 'cash')) === 'cash';
            if ($isCash) {
                if ($user->branch_id) {
                    $targetAccount = app(\App\Services\FinancialAccountService::class)->getDefaultCashAccount($businessId, $user->branch_id);
                }
                if (!$targetAccount) {
                    $primaryBranchId = \App\Models\Branch::where('business_id', $businessId)->where('is_primary', true)->value('id')
                        ?? \App\Models\Branch::where('business_id', $businessId)->value('id');
                    if ($primaryBranchId) {
                        $targetAccount = app(\App\Services\FinancialAccountService::class)->getDefaultCashAccount($businessId, $primaryBranchId);
                    }
                }
                if (!$targetAccount) {
                    $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                        ->where('type', 'cash')
                        ->where('status', 'active')
                        ->first();
                }
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
            return DB::transaction(function () use ($request, $user, $businessId, $supplier, $purchase, $amount, $targetAccount, $idempotencyKey) {
                // 1. Record balance deduction atomically (validates overpayment)
                $this->supplierBalanceService->recordPayment($supplier, $amount);

                // 2. If purchase allocated, update purchase paid & due amounts
                if ($purchase) {
                    $purchase->paid_amount = bcadd((string) $purchase->paid_amount, (string) $amount, 2);
                    $purchase->due_amount = bcsub((string) $purchase->total, (string) $purchase->paid_amount, 2);
                    $purchase->save();
                }

                // 3. Create SupplierPayment record
                $payment = SupplierPayment::create([
                    'business_id' => $businessId,
                    'supplier_id' => $supplier->id,
                    'purchase_id' => $purchase ? $purchase->id : null,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'date' => $request->date ?? now()->toDateString(),
                    'payment_method' => $request->payment_method ?? 'cash',
                    'notes' => $request->notes,
                    'idempotency_key' => $idempotencyKey,
                ]);

                // 4. Post physical outflow account_movement (validates sufficient account balance)
                app(\App\Services\AccountMovementService::class)->postOutflow($targetAccount, [
                    'branch_id' => $user->branch_id ?? $targetAccount->branch_id,
                    'movement_category' => 'supplier_payment',
                    'reference_type' => SupplierPayment::class,
                    'reference_id' => $payment->id,
                    'amount' => $amount,
                    'date' => $payment->date,
                    'description' => "Supplier Payment to {$supplier->name}" . ($purchase ? " (PO #{$purchase->po_number})" : ""),
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
                        'purchase_id' => $purchase ? $purchase->id : null,
                        'amount' => $amount,
                        'payment_method' => $payment->payment_method,
                        'financial_account_id' => $targetAccount->id,
                    ],
                    branchId: $user->branch_id ?? $targetAccount->branch_id,
                    businessId: (int) $businessId
                );

                return response()->json($payment->load(['supplier', 'purchase']), 201);
            });
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Reverse a supplier payment atomically (GAP-012).
     */
    public function reverse(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('record supplier payments')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();
        $payment = SupplierPayment::where('business_id', $businessId)->findOrFail($id);

        if ($payment->status === 'reversed') {
            return response()->json(['error' => 'Supplier payment has already been reversed.'], 422);
        }

        try {
            return DB::transaction(function () use ($payment, $user, $businessId) {
                // 1. Re-accrue supplier balance
                $supplier = Supplier::where('business_id', $businessId)->findOrFail($payment->supplier_id);
                $this->supplierBalanceService->reversePayment($supplier, (float) $payment->amount);

                // 2. If purchase allocated, restore purchase due & paid amounts
                if ($payment->purchase_id) {
                    $purchase = \App\Models\Purchase::where('business_id', $businessId)->find($payment->purchase_id);
                    if ($purchase) {
                        $purchase->paid_amount = max(0.00, (float) bcsub((string) $purchase->paid_amount, (string) $payment->amount, 2));
                        $purchase->due_amount = bcsub((string) $purchase->total, (string) $purchase->paid_amount, 2);
                        $purchase->save();
                    }
                }

                // 3. Mark payment as reversed
                $payment->update([
                    'status' => 'reversed',
                    'reversed_at' => now(),
                    'reversed_by' => $user->id,
                ]);

                // 4. Post compensating inflow to restore financial account if AccountMovement exists
                $originalMovement = \App\Models\AccountMovement::where('reference_type', SupplierPayment::class)
                    ->where('reference_id', $payment->id)
                    ->first();

                if ($originalMovement && $originalMovement->account_id) {
                    $account = \App\Models\FinancialAccount::find($originalMovement->account_id);
                    if ($account) {
                        app(\App\Services\AccountMovementService::class)->postInflow($account, [
                            'branch_id' => $originalMovement->branch_id ?? $user->branch_id,
                            'movement_category' => 'supplier_payment_reversal',
                            'reference_type' => SupplierPayment::class,
                            'reference_id' => $payment->id,
                            'amount' => (float) $payment->amount,
                            'date' => now()->toDateString(),
                            'description' => "Reversal of Supplier Payment #{$payment->id} for {$supplier->name}",
                            'user_id' => $user->id,
                        ]);
                    }
                }

                app(\App\Services\AuditService::class)->log(
                    logName: 'supplier',
                    event: 'reversed',
                    description: "Supplier payment #{$payment->id} of {$payment->amount} reversed for {$supplier->name}",
                    subject: $payment,
                    properties: [
                        'action' => 'supplier_payment_reversal',
                        'payment_id' => $payment->id,
                        'supplier_id' => $supplier->id,
                        'amount' => (float) $payment->amount,
                    ],
                    branchId: $user->branch_id ?? $originalMovement?->branch_id,
                    businessId: (int) $businessId
                );

                return response()->json($payment->fresh(['supplier', 'purchase']));
            });
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
