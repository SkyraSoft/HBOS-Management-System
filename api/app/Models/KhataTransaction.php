<?php

namespace App\Models;

use App\Models\Traits\Tenantable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KhataTransaction extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'customer_id',
        'type',
        'amount',
        'date',
        'notes'
    ];
}
