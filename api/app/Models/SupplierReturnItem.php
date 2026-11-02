<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_return_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplierReturn()
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
