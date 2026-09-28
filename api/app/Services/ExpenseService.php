<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    protected AccountMovementService $movementService;

    public function __construct(AccountMovementService $movementService)
    {
        $this->movementService = $movementService;
    }

    /**
     * Create an expense and post linked outflow account_movement atomically.
     */
    public function createExpense(array $data, int $businessId, ?int $userId = null): Expense
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Expense amount must be greater than zero.'],
            ]);
        }

        // Resolve Category
        $categoryId = (int) ($data['category_id'] ?? 0);
        $category = null;
        if ($categoryId > 0) {
            $category = ExpenseCategory::where('business_id', $businessId)->find($categoryId);
        }

        if (!$category && !empty($data['category'])) {
            $catName = trim($data['category']);
            $category = ExpenseCategory::where('business_id', $businessId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($catName)])
                ->first();
            if (!$category) {
                $category = ExpenseCategory::create([
                    'business_id' => $businessId,
                    'name' => $catName,
                ]);
            }
        }

        if (!$category) {
            throw ValidationException::withMessages([
                'category_id' => ['Selected expense category is invalid or does not belong to active business.'],
            ]);
        }

        // Resolve Financial Account
        $accountId = (int) ($data['financial_account_id'] ?? 0);
        $account = null;
        if ($accountId > 0) {
            $account = FinancialAccount::where('business_id', $businessId)->find($accountId);
        }

        if (!$account) {
            $branchId = $data['branch_id'] ?? null;
            if ($branchId) {
                $account = app(FinancialAccountService::class)->getDefaultCashAccount($businessId, (int)$branchId);
            }
        }

        if (!$account || $account->status !== 'active') {
            throw ValidationException::withMessages([
                'financial_account_id' => ['Selected financial account is invalid or inactive, or no active branch default cash drawer is configured.'],
            ]);
        }

        // Validate available balance for expense outflow
        $currentBalance = $this->movementService->calculateBalance($account);
        if ($amount > $currentBalance) {
            throw ValidationException::withMessages([
                'amount' => ["Insufficient account balance for expense outflow. Available: {$currentBalance}, requested: {$amount}"],
            ]);
        }

        $branchId = $data['branch_id'] ?? $account->branch_id;
        $description = $data['description'] ?? $data['notes'] ?? null;
        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey) {
            $existing = Expense::where('business_id', $businessId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($data, $businessId, $branchId, $userId, $category, $account, $amount, $idempotencyKey) {
            $expense = Expense::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'category_id' => $category->id,
                'financial_account_id' => $account->id,
                'category' => $category->name, // Snapshot display string
                'amount' => $amount,
                'date' => $data['date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'status' => 'posted',
                'idempotency_key' => $idempotencyKey,
            ]);

            // Post outflow movement (which validates sufficient balance)
            $this->movementService->postOutflow($account, [
                'branch_id' => $branchId,
                'movement_category' => 'expense',
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'amount' => $amount,
                'date' => $expense->date->toDateString(),
                'description' => "Expense: {$category->name} - " . ($expense->description ?? ''),
                'user_id' => $userId,
                'idempotency_key' => $idempotencyKey ? "exp_{$idempotencyKey}" : null,
            ]);

            return $expense->fresh(['categoryModel', 'financialAccount', 'branch']);
        });
    }

    /**
     * Void a posted expense and its linked outflow movement atomically.
     */
    public function voidExpense(Expense $expense, int $userId, string $reason): Expense
    {
        if ($expense->status === 'voided') {
            throw ValidationException::withMessages([
                'status' => ['Expense is already voided.'],
            ]);
        }

        return DB::transaction(function () use ($expense, $userId, $reason) {
            $expense->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $userId,
                'void_reason' => $reason,
            ]);

            $movement = AccountMovement::where('business_id', $expense->business_id)
                ->where('reference_type', Expense::class)
                ->where('reference_id', $expense->id)
                ->where('movement_category', 'expense')
                ->where('status', 'posted')
                ->first();

            if ($movement) {
                $this->movementService->voidMovement($movement, $userId, "Expense voided: {$reason}");
            }

            return $expense->fresh();
        });
    }
}
