<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Business extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'type',
        'address',
        'phone',
        'email',
        'logo',
        'currency'
    ];

    /**
     * Get all branches belonging to this business.
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get all users that belong to this business.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'business_user')->withTimestamps();
    }
}
