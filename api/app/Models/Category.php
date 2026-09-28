<?php

namespace App\Models;

use App\Models\Traits\Tenantable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'business_id',
        'name',
        'description'
    ];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
}
