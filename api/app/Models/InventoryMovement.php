<?php

namespace App\Models;

use App\Models\Traits\Branchable;
use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory, Tenantable, Branchable;

    protected $fillable = [
        'business_id',
        'branch_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'performed_by',
        'notes',
        'balance',
        'description',
    ];

    protected $attributes = [
        'balance' => '0.000',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'balance' => 'decimal:3',
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

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
