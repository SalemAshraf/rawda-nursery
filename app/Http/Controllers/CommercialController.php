<?php

namespace App\Http\Controllers;

use App\Models\SaasPlan;
use App\Models\SalesInquiry;
use App\Models\TenantSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CommercialController extends Controller
{
    public function landing()
    {
        return Inertia::render('Welcome', ['plans' => SaasPlan::catalog()]);
    }

    public function inquiry(Request $r)
    {
        $d = $r->validate(['nursery_name' => 'required|string|max:180', 'name' => 'required|string|max:120', 'email' => 'required|email|max:180', 'phone' => 'nullable|string|max:40', 'message' => 'nullable|string|max:2000', 'saas_plan_id' => ['nullable', Rule::exists('saas_plans', 'id')->where('published', true)], 'consent' => 'accepted']);
        unset($d['consent']);
        SalesInquiry::create($d);

        return back()->with('success', 'تم حفظ طلبك. لا يتم تحصيل أي مبلغ بهذه الخطوة.');
    }

    public function subscription(Request $r)
    {
        abort_unless($r->user()->role === 'owner', 403);
        $sub = TenantSubscription::forTenant($r->user()->tenant_id);

        return Inertia::render('Subscription/Index', ['subscription' => $sub ? $sub->only('id', 'status', 'period_ends_at', 'legacy_access') + ['plan' => $sub->plan?->only('name_ar', 'name_en')] : null, 'legacy' => $sub === null, 'plans' => SaasPlan::catalog(), 'requests' => SalesInquiry::where('tenant_id', $r->user()->tenant_id)->latest()->limit(10)->get(['id', 'status', 'created_at', 'saas_plan_id'])]);
    }

    public function requestPlan(Request $r)
    {
        abort_unless($r->user()->role === 'owner', 403);
        $d = $r->validate(['saas_plan_id' => ['nullable', Rule::exists('saas_plans', 'id')->where('published', true)], 'message' => 'nullable|string|max:2000']);
        SalesInquiry::create($d + ['tenant_id' => $r->user()->tenant_id, 'nursery_name' => $r->user()->tenant->name, 'name' => $r->user()->name, 'email' => $r->user()->email]);

        return back()->with('success','تم حفظ طلب الاشتراك للمراجعة، دون دفع أو تغيير باقتك الحالية.');
    }
}
