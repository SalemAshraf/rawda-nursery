<?php

namespace App\Http\Middleware;

use App\Models\TenantSubscription;
use Closure;
use Illuminate\Http\Request;

class SubscriptionAccess
{
    public function handle(Request $r, Closure $next)
    {
        if (! $r->isMethodSafe() && ! $r->routeIs('profile.*', 'subscription.*', 'setup.*')) {
            $sub = TenantSubscription::forTenant($r->user()->tenant_id);
            // Missing rows are pre-platform nurseries. Never expire or rewrite their access.
            if ($sub && ! $sub->writable()) {
                return redirect()->route('dashboard')->with('success', 'مساحة العمل للعرض فقط حالياً. على المالك مراجعة صفحة الاشتراك لطلب التفعيل.');
            }
        }

        return $next($r);
    }
}
