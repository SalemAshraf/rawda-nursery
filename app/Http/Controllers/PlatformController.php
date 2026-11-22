<?php

namespace App\Http\Controllers;

use App\Models\SaasPlan;
use App\Models\SalesInquiry;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PlatformController extends Controller
{
    private function audit(string $action, ?int $id = null): void
    {
        DB::table('platform_audits')->insert(['platform_admin_id' => Auth::guard('platform')->id(), 'action' => $action, 'subject_id' => $id, 'created_at' => now()]);
    }

    public function loginForm()
    {
        return Inertia::render('Platform/Login');
    }

    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'platform:'.strtolower($d['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'محاولات كثيرة. حاول بعد دقيقة.']);
        }
        if (! Auth::guard('platform')->attempt($d)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }
        RateLimiter::clear($key);
        $r->session()->regenerate();
        $this->audit('login');

        return redirect()->route('platform.index');
    }

    public function logout(Request $r)
    {
        $this->audit('logout');
        Auth::guard('platform')->logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('platform.login');
    }

    public function index(Request $r)
    {
        $filters = $r->validate(['search' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(['pending', 'active', 'past_due', 'suspended', 'legacy'])]]);
        $q = DB::table('tenants')->leftJoin('tenant_subscriptions as s', 's.tenant_id', '=', 'tenants.id')->leftJoin('saas_plans as p', 'p.id', '=', 's.saas_plan_id');
        if ($v = $filters['search'] ?? null) {
            $q->where('tenants.name', 'like', '%'.$v.'%');
        }
        if ($v = $filters['status'] ?? null) {
            if ($v === 'legacy') {
                $q->whereNull('s.id');
            } else {
                $q->where('s.status', $v);
            }
        }

        return Inertia::render('Platform/Index', [
            'stats' => ['nurseries' => DB::table('tenants')->count(), 'pending' => DB::table('tenant_subscriptions')->where('status', 'pending')->count(), 'inquiries' => SalesInquiry::where('status', 'new')->count(), 'plans' => SaasPlan::where('published', true)->count()],
            'nurseries' => $q->orderByDesc('tenants.id')->select(['tenants.id', 'tenants.name', 'tenants.currency', 'tenants.created_at', 's.status', 's.period_ends_at', 's.saas_plan_id', 'p.name_ar as plan_name'])->paginate(15)->withQueryString(),
            'plans' => SaasPlan::orderBy('sort')->get(),
            'inquiries' => SalesInquiry::latest()->paginate(15, ['*'], 'inquiries_page')->withQueryString(),
            'audits' => DB::table('platform_audits')->orderByDesc('id')->limit(30)->get(['action', 'subject_id', 'created_at']),
            'filters' => $filters,
        ]);
    }

    public function savePlan(Request $r, ?SaasPlan $plan = null)
    {
        $d = $r->validate(['name_ar' => 'required|string|max:120', 'name_en' => 'required|string|max:120', 'description_ar' => 'nullable|string|max:500', 'description_en' => 'nullable|string|max:500', 'price' => 'nullable|string|max:40', 'currency' => ['required', Rule::in(['EGP', 'SAR', 'AED', 'QAR', 'USD'])], 'interval' => ['required', Rule::in(['monthly', 'yearly'])], 'features_ar' => 'nullable|array|max:12', 'features_ar.*' => 'string|max:160', 'features_en' => 'nullable|array|max:12', 'features_en.*' => 'string|max:160', 'published' => 'required|boolean', 'sort' => 'required|integer|min:0|max:999']);
        $price = $d['price'] ?? null;
        $d['price_minor'] = $price === null || trim($price) === '' ? null : Money::toMinor($price);
        unset($d['price']);
        if ($price !== null && trim($price) !== '' && $d['price_minor'] === null) {
            throw ValidationException::withMessages(['price' => 'أدخل مبلغاً صحيحاً بحد أقصى منزلتين عشريتين.']);
        }
        if ($d['price_minor'] === 0) {
            throw ValidationException::withMessages(['price' => 'اترك السعر فارغاً لعرض سعر حسب الطلب، أو أدخل قيمة أكبر من صفر.']);
        }
        DB::transaction(function () use ($plan, $d) {
            $plan ??= new SaasPlan;
            $plan->fill($d)->save();
            $this->audit('plan.saved', $plan->id);
        });

        return back()->with('success', 'تم حفظ الباقة.');
    }

    public function updateSubscription(Request $r, Tenant $tenant)
    {
        $d = $r->validate(['status' => ['required', Rule::in(['pending', 'active', 'past_due', 'suspended'])], 'saas_plan_id' => 'nullable|exists:saas_plans,id', 'period_ends_at' => ['nullable', 'date', ...($r->input('status') === 'active' ? ['after:now'] : [])], 'operator_note' => 'nullable|string|max:2000', 'confirm' => 'accepted']);
        unset($d['confirm']);
        DB::transaction(function () use ($tenant, $d) {
            $tenant = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            TenantSubscription::updateOrCreate(['tenant_id' => $tenant->id], $d);
            $this->audit('subscription.'.$d['status'], $tenant->id);
        });

        return back()->with('success', 'تم تحديث حالة الاشتراك. لا يتم تحصيل أي دفعة.');
    }

    public function updateInquiry(Request $r, SalesInquiry $inquiry)
    {
        $d = $r->validate(['status' => ['required', Rule::in(['new', 'contacted', 'qualified', 'closed'])]]);
        DB::transaction(function () use ($d, $inquiry) {
            $inquiry->update($d);
            $this->audit('inquiry.'.$d['status'], $inquiry->id);
        });

        return back()->with('success','تم تحديث المتابعة.');
    }
}
