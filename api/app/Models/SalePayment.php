<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'sale_id',
        'payment_method',
        'amount',
        'financial_account_id',
        'reference_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }
}
