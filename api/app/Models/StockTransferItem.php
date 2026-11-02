<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'quantity_sent' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
