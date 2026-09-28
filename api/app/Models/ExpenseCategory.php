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
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
