<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use App\Models\Traits\Branchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, Tenantable, Branchable, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'category_id',
        'financial_account_id',
        'category',
        'amount',
        'date',
        'description',
        'status',
        'idempotency_key',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'voided_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categoryModel()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
