<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'supplier_id',
        'purchase_id',
        'user_id',
        'amount',
        'date',
        'payment_method',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'notes',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
