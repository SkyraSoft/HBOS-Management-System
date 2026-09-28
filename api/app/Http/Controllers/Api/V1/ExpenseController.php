<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Http\Middleware\ResolveActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class ExpenseController extends Controller
{
    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    protected function getActiveBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('view expenses') && !$user->hasRole('Branch Manager')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $this->getActiveBusinessId($request);
        $query = Expense::with(['categoryModel', 'financialAccount', 'branch', 'user', 'voidedBy'])
            ->where('business_id', $businessId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if (!$user->hasRole('Business Owner') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $expenses = $query->get();
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('record expenses') && !$user->hasRole('Branch Manager')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'category_id' => 'nullable|integer',
            'category' => 'nullable|string|max:255',
            'financial_account_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'branch_id' => 'nullable|integer',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        if (empty($request->category_id) && empty($request->category)) {
            throw ValidationException::withMessages([
                'category_id' => ['The category id or category name field is required.']
            ]);
        }

        $businessId = $this->getActiveBusinessId($request);

        try {
            $expense = $this->expenseService->createExpense($request->all(), $businessId, $user->id);

            app(\App\Services\AuditService::class)->log(
                logName: 'expense',
                event: 'created',
                description: "Expense #{$expense->id} of {$expense->amount} posted",
                subject: $expense,
                properties: [
                    'expense_id' => $expense->id,
                    'amount' => (float) $expense->amount,
                    'category' => $expense->category,
                    'description' => $expense->description,
                ],
                branchId: $expense->branch_id,
                businessId: $businessId
            );

            return response()->json($expense, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(Request $request, string $id)
    {
        $businessId = $this->getActiveBusinessId($request);
        $expense = Expense::with(['categoryModel', 'financialAccount', 'branch', 'user', 'voidedBy'])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return response()->json($expense);
    }

    public function update(Request $request, string $id)
    {
        return response()->json([
            'error' => 'Posted expenses cannot be edited. Use void endpoint if cancellation is required.'
        ], 422);
    }

    public function destroy(Request $request, string $id)
    {
        return response()->json([
            'error' => 'Posted expenses cannot be deleted. Use void endpoint.'
        ], 422);
    }

    public function void(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->can('void expenses') && !$user->hasRole('Branch Manager')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $expense = Expense::where('business_id', $businessId)->findOrFail($id);

        try {
            $voided = $this->expenseService->voidExpense($expense, $user->id, $request->reason);

            app(\App\Services\AuditService::class)->log(
                logName: 'expense',
                event: 'voided',
                description: "Expense #{$expense->id} voided. Reason: {$request->reason}",
                subject: $voided,
                properties: [
                    'expense_id' => $expense->id,
                    'amount' => (float) $expense->amount,
                    'reason' => $request->reason,
                ],
                branchId: $expense->branch_id,
                businessId: $businessId
            );

            return response()->json($voided);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
