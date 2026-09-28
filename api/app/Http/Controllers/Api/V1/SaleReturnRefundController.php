<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SaleReturn;
use App\Models\FinancialAccount;
use App\Services\AccountMovementService;
use App\Http\Middleware\ResolveActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class SaleReturnRefundController extends Controller
{
    protected AccountMovementService $movementService;

    public function __construct(AccountMovementService $movementService)
    {
        $this->movementService = $movementService;
    }

    protected function getActiveBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    public function settleRefund(Request $request, string $saleReturnId)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('settle refunds')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'financial_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $saleReturn = SaleReturn::where('business_id', $businessId)->findOrFail($saleReturnId);
        $account = FinancialAccount::where('business_id', $businessId)->findOrFail($request->financial_account_id);

        try {
            $movement = $this->movementService->settleSaleReturnRefund(
                $saleReturn,
                $account,
                (float) $request->amount,
                $request->date ?? now()->toDateString(),
                $user->id,
                $request->idempotency_key
            );

            app(\App\Services\AuditService::class)->log(
                logName: 'financial',
                event: 'settled',
                description: "Refund of {$request->amount} settled for Sale Return #{$saleReturn->id} via '{$account->name}'",
                subject: $movement,
                properties: [
                    'sale_return_id' => $saleReturn->id,
                    'account_id' => $account->id,
                    'amount' => (float) $request->amount,
                ],
                branchId: $saleReturn->branch_id,
                businessId: (int) $businessId
            );

            return response()->json($movement, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reverseRefund(Request $request, string $saleReturnId)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('settle refunds')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $saleReturn = SaleReturn::where('business_id', $businessId)->findOrFail($saleReturnId);

        try {
            $movement = $this->movementService->reverseSaleReturnRefund(
                $saleReturn,
                $user->id,
                $request->reason
            );

            app(\App\Services\AuditService::class)->log(
                logName: 'financial',
                event: 'reversed',
                description: "Refund for Sale Return #{$saleReturn->id} reversed. Reason: {$request->reason}",
                subject: $movement,
                properties: [
                    'sale_return_id' => $saleReturn->id,
                    'reason' => $request->reason,
                ],
                branchId: $saleReturn->branch_id,
                businessId: (int) $businessId
            );

            return response()->json($movement);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
