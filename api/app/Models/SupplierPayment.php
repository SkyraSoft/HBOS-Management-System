<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'supplier_id',
        'amount',
        'date',
        'payment_method',
        'notes'
    ];
}
