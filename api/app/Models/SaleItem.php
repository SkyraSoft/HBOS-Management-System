<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'barcode',
        'sku',
        'product_name',
        'quantity',
        'unit_price',
        'cost_price',
        'unit_cost',
        'discount',
        'total',
        'returned_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'returned_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
