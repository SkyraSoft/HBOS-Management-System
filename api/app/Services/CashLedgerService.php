<?php

namespace App\Services;

use App\Models\CashLedger;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashLedgerService
{
    /**
     * Record a cash ledger entry.
     * 
     * @param array $data Expected keys: branch_id, type (in/out), amount, reference_type, reference_id, description
     */
    public function recordTransaction(array $data)
    {
        return DB::transaction(function () use ($data) {
            
            if (!in_array($data['type'], ['in', 'out'])) {
                throw new Exception("Invalid cash transaction type. Must be 'in' or 'out'.");
            }

            // Create the ledger entry
            $ledger = CashLedger::create([
                'business_id' => \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId(),
                'branch_id' => $data['branch_id'] ?? (Auth::user()->branch_id ?? null),
                'type' => $data['type'],
                'amount' => $data['amount'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'recorded_by' => Auth::id(),
            ]);

            return $ledger;
        });
    }

    /**
     * Get the current cash balance for a branch.
     * 
     * @param int|null $branchId
     * @return float
     */
    public function getBalance(?int $branchId = null): float
    {
        $query = CashLedger::where('business_id', \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId());
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalIn = (clone $query)->where('type', 'in')->sum('amount');
        $totalOut = (clone $query)->where('type', 'out')->sum('amount');

        return (float) ($totalIn - $totalOut);
    }
}
