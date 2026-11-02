<?php

namespace App\Models;

use App\Models\Traits\Tenantable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'name',
        'monthly_budget',
    ];

    protected $casts = [
        'monthly_budget' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
