<?php

namespace App\Models;

use App\Models\Traits\Tenantable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'category_id',
        'brand_id',
        'subcategory_id',
        'name',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'wholesale_price',
        'stock',
        'min_stock',
        'allow_fractional',
        'sale_unit',
        'conversion_factor',
        'unit',
        'description',
        'image',
        'is_active'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'stock' => 'decimal:3',
        'min_stock' => 'decimal:3',
        'allow_fractional' => 'boolean',
        'conversion_factor' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function branchInventories()
    {
        return $this->hasMany(BranchInventory::class);
    }

    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class);
    }
}
