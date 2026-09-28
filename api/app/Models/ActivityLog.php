<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
    protected $table = 'activity_log';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'properties',
        'attribute_changes',
        'business_id',
        'branch_id',
    ];

    protected static function booted()
    {
        static::creating(function (ActivityLog $activity) {
            if (empty($activity->business_id)) {
                if (app()->has('active_business_id') && app('active_business_id')) {
                    $activity->business_id = (int) app('active_business_id');
                } elseif ($activity->subject instanceof Business) {
                    if ($activity->event !== 'deleted') {
                        $activity->business_id = (int) $activity->subject->id;
                    }
                } elseif ($activity->subject && isset($activity->subject->business_id)) {
                    $activity->business_id = (int) $activity->subject->business_id;
                }
            }

            if (empty($activity->branch_id)) {
                if ($activity->subject instanceof Branch) {
                    if ($activity->event !== 'deleted') {
                        $activity->branch_id = (int) $activity->subject->id;
                    }
                } elseif ($activity->subject && isset($activity->subject->branch_id)) {
                    $activity->branch_id = (int) $activity->subject->branch_id;
                }
            }
        });
    }

    /**
     * Get the business associated with this activity log.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch associated with this activity log.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope query to a specific business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope query to a specific branch.
     */
    public function scopeForBranch($query, ?int $branchId)
    {
        if ($branchId === null) {
            return $query;
        }
        return $query->where('branch_id', $branchId);
    }
}
