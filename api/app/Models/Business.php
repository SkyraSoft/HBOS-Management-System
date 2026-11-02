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
        'legal_name',
        'type',
        'address',
        'country',
        'timezone',
        'tax_number',
        'phone',
        'email',
        'website',
        'logo',
        'currency',
        'onboarding_status',
        'onboarding_step',
        'onboarding_completed_at'
    ];

    protected $casts = [
        'onboarding_step' => 'integer',
        'onboarding_completed_at' => 'datetime'
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
