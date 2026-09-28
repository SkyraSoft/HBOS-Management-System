<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialAccountService
{
    /**
     * Create a new FinancialAccount.
     */
    public function createAccount(array $data, int $businessId): FinancialAccount
    {
        $type = $data['type'] ?? 'cash';
        if (!in_array($type, ['cash', 'bank'])) {
            throw ValidationException::withMessages([
                'type' => ['Financial account type must be either cash or bank.'],
            ]);
        }

        $openingBalance = (float) ($data['opening_balance'] ?? 0);
        if ($openingBalance < 0) {
            throw ValidationException::withMessages([
                'opening_balance' => ['Opening balance cannot be negative.'],
            ]);
        }

        $branchId = null;
        if ($type === 'cash') {
            if (empty($data['branch_id'])) {
                throw ValidationException::withMessages([
                    'branch_id' => ['Branch is required for cash accounts.'],
                ]);
            }
            $branchId = (int) $data['branch_id'];
        }

        $isDefault = !empty($data['is_default']);
        $status = $data['status'] ?? 'active';

        return DB::transaction(function () use ($data, $businessId, $branchId, $type, $openingBalance, $isDefault, $status) {
            if ($isDefault && $type === 'cash' && $status === 'active') {
                $this->ensureSingleActiveDefault($businessId, $branchId);
            }

            $account = FinancialAccount::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'name' => trim($data['name']),
                'type' => $type,
                'account_number' => $data['account_number'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'opening_balance' => $openingBalance,
                'balance' => $openingBalance,
                'is_default' => $isDefault,
                'status' => $status,
            ]);

            return $account;
        });
    }

    /**
     * Update FinancialAccount metadata.
     */
    public function updateAccount(FinancialAccount $account, array $data): FinancialAccount
    {
        return DB::transaction(function () use ($account, $data) {
            // Guard opening_balance immutability if movements exist
            if (array_key_exists('opening_balance', $data)) {
                $newOpening = (float) $data['opening_balance'];
                if ($newOpening < 0) {
                    throw ValidationException::withMessages([
                        'opening_balance' => ['Opening balance cannot be negative.'],
                    ]);
                }

                if ((float)$account->opening_balance !== $newOpening) {
                    $hasMovements = AccountMovement::where('account_id', $account->id)->exists();
                    if ($hasMovements) {
                        throw ValidationException::withMessages([
                            'opening_balance' => ['Opening balance is permanently immutable after account activity exists.'],
                        ]);
                    }
                    $account->opening_balance = $newOpening;
                }
            }

            if (isset($data['name'])) {
                $account->name = trim($data['name']);
            }
            if (array_key_exists('account_number', $data)) {
                $account->account_number = $data['account_number'];
            }
            if (array_key_exists('bank_name', $data)) {
                $account->bank_name = $data['bank_name'];
            }
            if (isset($data['status'])) {
                $account->status = $data['status'];
            }

            if (array_key_exists('is_default', $data)) {
                $newIsDefault = !empty($data['is_default']);
                if ($newIsDefault && $account->type === 'cash' && $account->status === 'active') {
                    $this->ensureSingleActiveDefault($account->business_id, $account->branch_id, $account->id);
                }
                $account->is_default = $newIsDefault;
            }

            $account->save();

            // Recalculate balance to ensure cache alignment
            app(AccountMovementService::class)->recalculateBalance($account);

            return $account->fresh();
        });
    }

    /**
     * Ensure at most one active default cash account per Branch.
     */
    protected function ensureSingleActiveDefault(int $businessId, int $branchId, ?int $ignoreAccountId = null): void
    {
        $query = FinancialAccount::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('type', 'cash')
            ->where('is_default', true)
            ->where('status', 'active');

        if ($ignoreAccountId) {
            $query->where('id', '!=', $ignoreAccountId);
        }

        $existingDefault = $query->lockForUpdate()->first();
        if ($existingDefault) {
            throw ValidationException::withMessages([
                'is_default' => ['An active default cash drawer already exists for this branch. Please deactivate or unset default status on the existing drawer first.'],
            ]);
        }
    }

    /**
     * Get active default cash drawer for a branch.
     */
    public function getDefaultCashAccount(int $businessId, int $branchId): ?FinancialAccount
    {
        return FinancialAccount::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('type', 'cash')
            ->where('is_default', true)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Delete account if no movements or non-zero balance exist.
     */
    public function deleteAccount(FinancialAccount $account): void
    {
        if ($account->movements()->exists() || (float) $account->current_balance != 0) {
            throw ValidationException::withMessages([
                'account' => ['Cannot delete account with existing movements or non-zero balance. Please deactivate the account instead.'],
            ]);
        }

        $account->delete();
    }
}

