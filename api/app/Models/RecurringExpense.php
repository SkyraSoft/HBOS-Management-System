<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'category',
        'amount',
        'frequency',
        'next_due_date',
        'description'
    ];
}
