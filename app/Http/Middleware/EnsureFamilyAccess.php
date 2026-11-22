<?php

namespace App\Http\Middleware;

use App\Models\FamilyAccount;
use App\Models\TenantSubscription;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class EnsureFamilyAccess
{
    public function handle(Request $r, Closure $next)
    {
        $api = $r->is('api/*');
        $parent = $api ? $r->user() : $r->user('family');
        abort_unless($parent instanceof FamilyAccount && $parent->tenant, 403);
        abort_unless($parent->active || $r->routeIs('family.logout', 'family.api.logout'), 401);
        if ($api) {
            abort_unless($parent->currentAccessToken() instanceof PersonalAccessToken && $parent->tokenCan('family'), 403);
        }
        if (! $r->isMethodSafe() && ! $r->routeIs('family.logout', 'family.api.logout')) {
            $sub = TenantSubscription::forTenant($parent->tenant_id);
            abort_if($sub && ! $sub->writable(), 423, 'The nursery is currently read-only.');
        }
        $r->attributes->set('family_account', $parent);

        return $next($r);
    }
}
