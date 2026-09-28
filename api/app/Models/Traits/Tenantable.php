<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait Tenantable
{
    /**
     * Boot the tenantable trait for a model.
     */
    protected static function bootTenantable()
    {
        // Add the global scope
        static::addGlobalScope(new TenantScope);

        // Automatically set the business_id on creation if missing
        static::creating(function ($model) {
            if (empty($model->business_id)) {
                $businessId = \App\Http\Middleware\ResolveActiveBusiness::getActiveBusinessId();
                if ($businessId) {
                    $model->business_id = $businessId;
                }
            }
        });
    }
}
