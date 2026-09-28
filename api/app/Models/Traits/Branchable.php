<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait Branchable
{
    /**
     * Boot the branchable trait for a model.
     * Note: Global BranchScope is intentionally removed to avoid hidden filtering.
     */
    protected static function bootBranchable()
    {
        // Automatically set the branch_id on creation if missing and user has one
        static::creating(function ($model) {
            if (empty($model->branch_id) && Auth::check() && Auth::user()->branch_id) {
                $model->branch_id = Auth::user()->branch_id;
            }
        });
    }

    /**
     * Scope query to a specific branch ID.
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where($this->getTable() . '.branch_id', $branchId);
    }
}
