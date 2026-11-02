<?php

namespace App\Models;

use App\Models\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class StockTransfer extends Model
{
    use HasFactory, Tenantable, LogsActivity;

    protected $fillable = [
        'business_id',
        'transfer_number',
        'from_branch_id',
        'to_branch_id',
        'status',
        'notes',
        'dispatched_by',
        'dispatched_at',
        'received_by',
        'received_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['transfer_number', 'from_branch_id', 'to_branch_id', 'status', 'dispatched_at', 'received_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('inventory');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }

    public function dispatchedBy()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDispatched(): bool
    {
        return $this->status === 'dispatched';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
