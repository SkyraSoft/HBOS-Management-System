<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use App\Models\Traits\Branchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAccount extends Model
{
    use HasFactory, Tenantable, Branchable, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'type',
        'account_number',
        'bank_name',
        'opening_balance',
        'is_default',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($account) {
            if ($account->balance === null) {
                $account->balance = $account->opening_balance ?? 0;
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function movements()
    {
        return $this->hasMany(AccountMovement::class, 'account_id');
    }
}
