<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegisterSession extends Model
{
    use HasFactory;

    protected $table = 'cash_register_sessions';

    protected $fillable = [
        'business_id',
        'branch_id',
        'financial_account_id',
        'user_id',
        'closed_by_user_id',
        'opening_float',
        'opened_at',
        'closing_cash_counted',
        'closing_cash_expected',
        'cash_variance',
        'variance_reason',
        'reconciliation_movement_id',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_float' => 'decimal:2',
        'closing_cash_counted' => 'decimal:2',
        'closing_cash_expected' => 'decimal:2',
        'cash_variance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function reconciliationMovement()
    {
        return $this->belongsTo(AccountMovement::class, 'reconciliation_movement_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
