<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Services\FinancialAccountService;
use App\Services\AccountMovementService;
use App\Http\Middleware\ResolveActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class FinancialAccountController extends Controller
{
    protected FinancialAccountService $accountService;
    protected AccountMovementService $movementService;

    public function __construct(
        FinancialAccountService $accountService,
        AccountMovementService $movementService
    ) {
        $this->accountService = $accountService;
        $this->movementService = $movementService;
    }

    protected function getActiveBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $businessId = $this->getActiveBusinessId($request);

        $query = FinancialAccount::with('branch')
            ->where('business_id', $businessId);

        if (!$user->hasRole('Business Owner')) {
            // Manager sees assigned branch cash drawers only (central bank accounts denied by default)
            if ($user->hasRole('Branch Manager')) {
                $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
                if ($user->branch_id) {
                    $userBranchIds[] = $user->branch_id;
                }
                $userBranchIds = array_unique(array_filter($userBranchIds));

                $query->where('type', 'cash')->whereIn('branch_id', $userBranchIds);
            } else {
                // Salesperson sees assigned branch default cash drawer only
                $query->where('type', 'cash')->where('is_default', true);
                if ($user->branch_id) {
                    $query->where('branch_id', $user->branch_id);
                }
            }
        }

        $accounts = $query->get();

        // Ensure cached balances are up to date
        foreach ($accounts as $account) {
            $this->movementService->recalculateBalance($account);
        }

        return response()->json($accounts);
    }

    protected function authorizeAccountAccess($user, FinancialAccount $account): void
    {
        if ($user->hasRole('Business Owner')) {
            return;
        }

        if ($user->hasRole('Branch Manager')) {
            if ($account->type !== 'cash' || !$account->branch_id) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Branch Manager is not authorized to access central bank accounts.');
            }

            $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
            if ($user->branch_id) {
                $userBranchIds[] = $user->branch_id;
            }
            $userBranchIds = array_unique(array_filter($userBranchIds));

            if (!in_array((int)$account->branch_id, array_map('intval', $userBranchIds))) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Branch Manager is not authorized to access unassigned branch cash drawers.');
            }

            return;
        }

        throw new \Illuminate\Auth\Access\AuthorizationException('Salesperson is not authorized to view financial account details.');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('manage financial accounts')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank',
            'branch_id' => 'nullable|integer',
            'account_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $businessId = $this->getActiveBusinessId($request);

        try {
            $account = $this->accountService->createAccount($request->all(), $businessId);

            app(\App\Services\AuditService::class)->log(
                logName: 'financial',
                event: 'created',
                description: "Financial account '{$account->name}' ({$account->type}) created",
                subject: $account,
                properties: [
                    'account_id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'opening_balance' => (float) $account->opening_balance,
                ],
                branchId: $account->branch_id,
                businessId: (int) $businessId
            );

            return response()->json($account, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $businessId = $this->getActiveBusinessId($request);
        $account = FinancialAccount::with('branch')
            ->where('business_id', $businessId)
            ->findOrFail($id);

        try {
            $this->authorizeAccountAccess($user, $account);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $this->movementService->recalculateBalance($account);

        return response()->json($account);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('manage financial accounts')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $account = FinancialAccount::where('business_id', $businessId)->findOrFail($id);

        try {
            $updated = $this->accountService->updateAccount($account, $request->all());
            return response()->json($updated);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('manage financial accounts')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $this->getActiveBusinessId($request);
        $account = FinancialAccount::where('business_id', $businessId)->findOrFail($id);

        try {
            $this->accountService->deleteAccount($account);
            return response()->json(['message' => 'Account deleted successfully.']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function movements(Request $request, string $id)
    {
        $user = $request->user();
        $businessId = $this->getActiveBusinessId($request);
        $account = FinancialAccount::where('business_id', $businessId)->findOrFail($id);

        try {
            $this->authorizeAccountAccess($user, $account);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $statement = $this->movementService->statement($account, $startDate, $endDate);
        return response()->json($statement);
    }

    public function capitalIn(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Capital injection requires Business Owner authorization.'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $account = FinancialAccount::where('business_id', $businessId)->findOrFail($id);

        try {
            $movement = $this->movementService->postInflow($account, [
                'movement_category' => 'capital_in',
                'amount' => (float) $request->amount,
                'date' => $request->date ?? now()->toDateString(),
                'description' => $request->description ?? 'Capital Injection',
                'user_id' => $user->id,
                'idempotency_key' => $request->idempotency_key,
                'branch_id' => $account->branch_id,
            ]);

            app(\App\Services\AuditService::class)->log(
                logName: 'financial',
                event: 'created',
                description: "Capital injection of {$request->amount} into account '{$account->name}'",
                subject: $movement,
                properties: [
                    'action' => 'capital_in',
                    'account_id' => $account->id,
                    'amount' => (float) $request->amount,
                    'description' => $request->description,
                ],
                branchId: $account->branch_id,
                businessId: (int) $businessId
            );

            return response()->json($movement, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        }
    }

    public function capitalOut(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Capital withdrawal requires Business Owner authorization.'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $account = FinancialAccount::where('business_id', $businessId)->findOrFail($id);

        try {
            $movement = $this->movementService->postOutflow($account, [
                'movement_category' => 'capital_out',
                'amount' => (float) $request->amount,
                'date' => $request->date ?? now()->toDateString(),
                'description' => $request->description ?? 'Capital Withdrawal',
                'user_id' => $user->id,
                'idempotency_key' => $request->idempotency_key,
                'branch_id' => $account->branch_id,
            ]);

            app(\App\Services\AuditService::class)->log(
                logName: 'financial',
                event: 'created',
                description: "Capital withdrawal of {$request->amount} from account '{$account->name}'",
                subject: $movement,
                properties: [
                    'action' => 'capital_out',
                    'account_id' => $account->id,
                    'amount' => (float) $request->amount,
                    'description' => $request->description,
                ],
                branchId: $account->branch_id,
                businessId: (int) $businessId
            );

            return response()->json($movement, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        }
    }
}
