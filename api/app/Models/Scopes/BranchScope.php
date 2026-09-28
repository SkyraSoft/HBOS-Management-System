<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Check if there is an authenticated user and they have a branch_id
        // If they have a branch_id, restrict data to that branch.
        // If branch_id is null (e.g. Business Owner), they can see all branches.
        if (Auth::check() && Auth::user()->branch_id) {
            $builder->where($model->getTable() . '.branch_id', Auth::user()->branch_id);
        }
    }
}
