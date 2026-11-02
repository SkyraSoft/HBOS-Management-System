<?php

namespace App\Models;

use App\Models\Traits\Branchable;
use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    use HasFactory, Tenantable, Branchable;

    protected $fillable = [
        'business_id',
        'branch_id',
        'purchase_id',
        'supplier_id',
        'return_number',
        'total_amount',
        'status',
        'reason',
        'created_by'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(SupplierReturnItem::class);
    }
}
