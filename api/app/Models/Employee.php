<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Tenantable;

class Employee extends Model
{
    use Tenantable;

    protected $fillable = [
        'business_id',
        'name',
        'role',
        'phone',
        'salary_amount',
        'payment_cycle',
        'next_payment_date',
        'is_active',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
