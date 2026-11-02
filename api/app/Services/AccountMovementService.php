<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\AccountTransfer;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountMovementService
{
    /**
     * Calculate authoritative raw account balance using fixed-decimal BCMath:
     * opening_balance + SUM(posted inflows) - SUM(posted outflows)
     */
    public function calculateBalance(FinancialAccount $account): string
    {
        $inflows = AccountMovement::where('account_id', $account->id)
            ->where('status', 'posted')
            ->where('type', 'inflow')
            ->sum('amount');

        $outflows = AccountMovement::where('account_id', $account->id)
            ->where('status', 'posted')
            ->where('type', 'outflow')
            ->sum('amount');

        $opening = (string) ($account->opening_balance ?? '0.00');
        $inflowsStr = (string) ($inflows ?? '0.00');
        $outflowsStr = (string) ($outflows ?? '0.00');

        return bcsub(bcadd($opening, $inflowsStr, 2), $outflowsStr, 2);
    }

    /**
     * Recalculate and update cached balance on financial_accounts record using fixed-decimal BCMath.
     */
    public function recalculateBalance(FinancialAccount $account): string
    {
        $calculated = $this->calculateBalance($account);

        // Direct DB update to prevent unsafe mass-assignment / uncalibrated mutation
        DB::table('financial_accounts')
            ->where('id', $account->id)
            ->update([
                'balance' => $calculated,
                'updated_at' => now(),
            ]);

        $account->balance = $calculated;
        return $calculated;
    }

    /**
     * Post an inflow movement with strict row-lock serialization for Day Close concurrency.
     */
    public function postInflow(FinancialAccount $account, array $data): AccountMovement
    {
        $amountStr = (string) ($data['amount'] ?? '0');
        if (bccomp($amountStr, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Inflow amount must be greater than zero.'],
            ]);
        }

        return DB::transaction(function () use ($account, $data, $amountStr) {
            // Lock account row for concurrent write serialization with Day Close
            $lockedAccount = FinancialAccount::where('id', $account->id)->lockForUpdate()->first();
            if (!$lockedAccount) {
                throw ValidationException::withMessages([
                    'account_id' => ['Financial account not found.'],
                ]);
            }

            // Idempotency check if key provided
            if (!empty($data['idempotency_key'])) {
                $existing = AccountMovement::where('business_id', $lockedAccount->business_id)
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $movement = AccountMovement::create([
                'business_id' => $lockedAccount->business_id,
                'branch_id' => $data['branch_id'] ?? $lockedAccount->branch_id,
                'account_id' => $lockedAccount->id,
                'type' => 'inflow',
                'movement_category' => $data['movement_category'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'amount' => $amountStr,
                'date' => $data['date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'status' => 'posted',
            ]);

            $this->recalculateBalance($lockedAccount);
            $account->balance = $lockedAccount->balance;

            return $movement;
        });
    }

    /**
     * Post an outflow movement with strict negative-balance rejection and row locking.
     */
    public function postOutflow(FinancialAccount $account, array $data): AccountMovement
    {
        $amountStr = (string) ($data['amount'] ?? '0');
        if (bccomp($amountStr, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Outflow amount must be greater than zero.'],
            ]);
        }

        return DB::transaction(function () use ($account, $data, $amountStr) {
            // Lock account row for concurrent available balance validation
            $lockedAccount = FinancialAccount::where('id', $account->id)->lockForUpdate()->first();
            if (!$lockedAccount) {
                throw ValidationException::withMessages([
                    'account_id' => ['Financial account not found.'],
                ]);
            }

            $currentBalance = $this->calculateBalance($lockedAccount);

            if (bccomp($amountStr, $currentBalance, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => ["Insufficient account balance. Available: {$currentBalance}, requested outflow: {$amountStr}"],
                ]);
            }

            // Idempotency check if key provided
            if (!empty($data['idempotency_key'])) {
                $existing = AccountMovement::where('business_id', $lockedAccount->business_id)
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $movement = AccountMovement::create([
                'business_id' => $lockedAccount->business_id,
                'branch_id' => $data['branch_id'] ?? $lockedAccount->branch_id,
                'account_id' => $lockedAccount->id,
                'type' => 'outflow',
                'movement_category' => $data['movement_category'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'amount' => $amountStr,
                'date' => $data['date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'status' => 'posted',
            ]);

            $this->recalculateBalance($lockedAccount);
            $account->balance = $lockedAccount->balance;

            return $movement;
        });
    }

    /**
     * Perform cross-account transfer atomically with deterministic lock ordering.
     */
    public function transfer(
        FinancialAccount $source,
        FinancialAccount $destination,
        float|string $amount,
        string $date,
        ?string $notes = null,
        ?int $userId = null,
        ?string $idempotencyKey = null
    ): AccountTransfer {
        if ($source->business_id !== $destination->business_id) {
            throw ValidationException::withMessages([
                'destination_account_id' => ['Transfer source and destination must belong to the same business.'],
            ]);
        }

        if ($source->id === $destination->id) {
            throw ValidationException::withMessages([
                'destination_account_id' => ['Transfer source and destination accounts cannot be identical.'],
            ]);
        }

        if ($source->status !== 'active' || $destination->status !== 'active') {
            throw ValidationException::withMessages([
                'status' => ['Both transfer source and destination accounts must be active.'],
            ]);
        }

        $amountStr = (string) $amount;
        if (bccomp($amountStr, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Transfer amount must be greater than zero.'],
            ]);
        }

        if ($idempotencyKey) {
            $existingTransfer = AccountTransfer::where('business_id', $source->business_id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existingTransfer) {
                if (
                    (int) $existingTransfer->source_account_id !== (int) $source->id ||
                    (int) $existingTransfer->destination_account_id !== (int) $destination->id ||
                    bccomp((string) $existingTransfer->amount, $amountStr, 2) !== 0
                ) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => ['Idempotency key has already been used with different transfer parameters.'],
                    ]);
                }
                return $existingTransfer;
            }
        }

        return DB::transaction(function () use ($source, $destination, $amountStr, $date, $notes, $userId, $idempotencyKey) {
            // Lock in deterministic ID order to prevent deadlocks
            $firstId = min($source->id, $destination->id);
            $secondId = max($source->id, $destination->id);

            FinancialAccount::where('id', $firstId)->lockForUpdate()->first();
            FinancialAccount::where('id', $secondId)->lockForUpdate()->first();

            $sourceBalance = $this->calculateBalance($source);
            if (bccomp($amountStr, $sourceBalance, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => ["Insufficient account balance for transfer. Available: {$sourceBalance}, requested: {$amountStr}"],
                ]);
            }

            $transfer = AccountTransfer::create([
                'business_id' => $source->business_id,
                'source_account_id' => $source->id,
                'destination_account_id' => $destination->id,
                'amount' => $amountStr,
                'date' => $date,
                'notes' => $notes,
                'user_id' => $userId,
                'idempotency_key' => $idempotencyKey,
                'status' => 'posted',
            ]);

            // Post paired movements
            $this->postOutflow($source, [
                'movement_category' => 'transfer_out',
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'amount' => $amountStr,
                'date' => $date,
                'description' => "Transfer out to {$destination->name}",
                'user_id' => $userId,
                'branch_id' => $source->branch_id,
            ]);

            $this->postInflow($destination, [
                'movement_category' => 'transfer_in',
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'amount' => $amountStr,
                'date' => $date,
                'description' => "Transfer in from {$source->name}",
                'user_id' => $userId,
                'branch_id' => $destination->branch_id,
            ]);

            return $transfer;
        });
    }

    /**
     * Settle SaleReturn refund.
     */
    public function settleSaleReturnRefund(
        SaleReturn $saleReturn,
        FinancialAccount $account,
        float|string $amount,
        string $date,
        ?int $userId = null,
        ?string $idempotencyKey = null
    ): AccountMovement {
        if ($account->business_id !== $saleReturn->business_id) {
            throw ValidationException::withMessages([
                'financial_account_id' => ['Financial account must belong to the sale return business.'],
            ]);
        }

        if ($account->status !== 'active') {
            throw ValidationException::withMessages([
                'financial_account_id' => ['Financial account is not active.'],
            ]);
        }

        $refundExposure = (string) $saleReturn->refund_amount;
        $amountStr = (string) $amount;
        if (bccomp($amountStr, $refundExposure, 2) > 0) {
            throw ValidationException::withMessages([
                'amount' => ["Refund settlement amount ({$amountStr}) cannot exceed SaleReturn refund exposure ({$refundExposure})."],
            ]);
        }

        // Check one-time refund settlement rule
        $existingMovement = AccountMovement::where('business_id', $saleReturn->business_id)
            ->where('reference_type', get_class($saleReturn))
            ->where('reference_id', $saleReturn->id)
            ->where('movement_category', 'refund')
            ->where('status', 'posted')
            ->first();

        if ($existingMovement) {
            throw ValidationException::withMessages([
                'sale_return_id' => ['This SaleReturn has already been physically refund-settled.'],
            ]);
        }

        return DB::transaction(function () use ($saleReturn, $account, $amountStr, $date, $userId, $idempotencyKey) {
            return $this->postOutflow($account, [
                'movement_category' => 'refund',
                'reference_type' => get_class($saleReturn),
                'reference_id' => $saleReturn->id,
                'amount' => $amountStr,
                'date' => $date,
                'description' => "Refund settlement for SaleReturn #{$saleReturn->id}",
                'user_id' => $userId,
                'idempotency_key' => $idempotencyKey,
                'branch_id' => $saleReturn->branch_id ?? $account->branch_id,
            ]);
        });
    }

    /**
     * Reverse a posted sale return refund settlement movement.
     */
    public function reverseSaleReturnRefund(
        SaleReturn $saleReturn,
        int $userId,
        string $reason
    ): AccountMovement {
        $movement = AccountMovement::where('business_id', $saleReturn->business_id)
            ->where('reference_type', get_class($saleReturn))
            ->where('reference_id', $saleReturn->id)
            ->where('movement_category', 'refund')
            ->where('status', 'posted')
            ->first();

        if (!$movement) {
            throw ValidationException::withMessages([
                'sale_return_id' => ['No active posted refund movement exists for this SaleReturn.'],
            ]);
        }

        return $this->voidMovement($movement, $userId, $reason);
    }

    /**
     * Void a posted movement (domain-controlled).
     */
    public function voidMovement(AccountMovement $movement, int $userId, string $reason): AccountMovement
    {
        if ($movement->status === 'voided') {
            throw ValidationException::withMessages([
                'status' => ['Movement is already voided.'],
            ]);
        }

        return DB::transaction(function () use ($movement, $userId, $reason) {
            $movement->update([
                'status' => 'voided',
                'reversed_at' => now(),
                'reversed_by' => $userId,
                'reversal_reason' => $reason,
            ]);

            $account = FinancialAccount::where('id', $movement->account_id)->lockForUpdate()->first();
            if ($account) {
                $this->recalculateBalance($account);
            }

            return $movement;
        });
    }

    /**
     * Generate chronological account statement ledger.
     */
    public function statement(FinancialAccount $account, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = AccountMovement::where('account_id', $account->id)
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $movements = $query->get();

        $runningBalance = (string) ($account->opening_balance ?? '0.00');
        $items = [];

        foreach ($movements as $m) {
            if ($m->status === 'posted') {
                if ($m->type === 'inflow') {
                    $runningBalance = bcadd($runningBalance, (string) $m->amount, 2);
                } else {
                    $runningBalance = bcsub($runningBalance, (string) $m->amount, 2);
                }
            }

            $items[] = [
                'id' => $m->id,
                'date' => $m->date->toDateString(),
                'type' => $m->type,
                'movement_category' => $m->movement_category,
                'amount' => (float) $m->amount,
                'status' => $m->status,
                'description' => $m->description,
                'running_balance' => (float) $runningBalance,
                'created_at' => $m->created_at->toIso8601String(),
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'opening_balance' => (float) $account->opening_balance,
                'current_balance' => (float) $this->calculateBalance($account),
            ],
            'statement_baseline' => (float) $account->opening_balance,
            'movements' => $items,
        ];
    }
}
