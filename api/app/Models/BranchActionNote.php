<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BranchActionNote extends Model
{
    use HasFactory, Tenantable, LogsActivity;

    protected $fillable = [
        'business_id',
        'branch_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'priority',
        'status',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['branch_id', 'title', 'priority', 'status', 'assigned_to', 'resolved_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('governance');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
