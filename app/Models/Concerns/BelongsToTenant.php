<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

// Single-database tenancy: every tenant-owned model is automatically filtered
// to the authenticated user's tenant and stamped with tenant_id on create.
// Queries without an authenticated user (console, seeding) are unscoped; use
// the user context explicitly in tests for tenant-isolation assertions.
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', Auth::user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && Auth::check()) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    public static function withoutTenantScope(callable $callback): mixed
    {
        return $callback(static::withoutGlobalScope('tenant'));
    }
}
