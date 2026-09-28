<?php

namespace App\Models;

use App\Models\Traits\Tenantable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'key',
        'value',
        'type'
    ];
}
