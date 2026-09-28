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
        'stock',
        'min_stock',
        'unit',
        'description',
        'image',
        'is_active'
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
}
