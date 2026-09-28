<?php

namespace App\Models;

use App\Models\Traits\Branchable;
use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashLedger extends Model
{
    use HasFactory, Tenantable, Branchable;

    protected $fillable = [
        'business_id',
        'branch_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'recorded_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
