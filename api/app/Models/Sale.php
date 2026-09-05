<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'user_id',
        'customer_id',
        'invoice_number',
        'date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'notes'
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
