<?php

namespace App\Models;

use App\Models\Traits\Tenantable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringExpense extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'category',
        'amount',
        'frequency',
        'next_due_date',
        'description'
    ];
}
