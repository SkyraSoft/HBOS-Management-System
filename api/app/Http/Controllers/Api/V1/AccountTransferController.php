<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\AccountTransfer;
use App\Services\AccountMovementService;
use App\Http\Middleware\ResolveActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class AccountTransferController extends Controller
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

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('perform account transfers')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $this->getActiveBusinessId($request);
        $transfers = AccountTransfer::with(['sourceAccount', 'destinationAccount', 'user'])
            ->where('business_id', $businessId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($transfers);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('perform account transfers')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'source_account_id' => 'required|integer',
            'destination_account_id' => 'required|integer|different:source_account_id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $businessId = $this->getActiveBusinessId($request);

        $source = FinancialAccount::where('business_id', $businessId)->findOrFail($request->source_account_id);
        $destination = FinancialAccount::where('business_id', $businessId)->findOrFail($request->destination_account_id);

        try {
            $transfer = $this->movementService->transfer(
                $source,
                $destination,
                (float) $request->amount,
                $request->date,
                $request->notes,
                $user->id,
                $request->idempotency_key
            );

            app(\App\Services\AuditService::class)->log(
                logName: 'financial',
                event: 'created',
                description: "Transfer of {$request->amount} from '{$source->name}' to '{$destination->name}'",
                subject: $transfer,
                properties: [
                    'action' => 'account_transfer',
                    'transfer_id' => $transfer->id,
                    'source_account_id' => $source->id,
                    'destination_account_id' => $destination->id,
                    'amount' => (float) $request->amount,
                    'notes' => $request->notes,
                ],
                branchId: $source->branch_id ?? $destination->branch_id,
                businessId: (int) $businessId
            );

            return response()->json($transfer->load(['sourceAccount', 'destinationAccount']), 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
