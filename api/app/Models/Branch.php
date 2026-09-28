<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Branch extends Model
{
    use HasFactory, Tenantable, LogsActivity;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'phone',
        'address',
        'city',
        'is_primary',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'phone', 'address', 'city', 'is_primary', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('governance');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'branch_user')->withTimestamps();
    }
}
