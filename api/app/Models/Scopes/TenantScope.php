<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $businessId = \App\Http\Middleware\ResolveActiveBusiness::getActiveBusinessId();

        if ($businessId) {
            $builder->where($model->getTable() . '.business_id', $businessId);
        } elseif (Auth::check()) {
            // Authenticated user with ambiguous or missing business context must not leak records
            $builder->whereRaw('1 = 0');
        }
    }
}
