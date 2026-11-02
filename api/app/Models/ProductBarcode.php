<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBarcode extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'product_id',
        'barcode',
        'barcode_type',
    ];

    protected $casts = [
        'barcode' => 'string',
        'barcode_type' => 'string',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
