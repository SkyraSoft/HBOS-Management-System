<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransfer extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'source_account_id',
        'destination_account_id',
        'amount',
        'date',
        'notes',
        'user_id',
        'idempotency_key',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function sourceAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'source_account_id');
    }

    public function destinationAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
