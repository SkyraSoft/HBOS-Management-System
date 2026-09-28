<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use App\Models\Traits\Branchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleReturn extends Model
{
    use HasFactory, Tenantable, Branchable;

    protected $fillable = [
        'business_id',
        'branch_id',
        'sale_id',
        'user_id',
        'return_number',
        'refund_amount',
        'reason',
        'idempotency_key',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
