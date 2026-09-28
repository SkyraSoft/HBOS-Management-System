<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchInventory extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'branch_id',
        'product_id',
        'quantity_on_hand',
        'minimum_stock',
        'reorder_level',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'minimum_stock' => 'integer',
        'reorder_level' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
