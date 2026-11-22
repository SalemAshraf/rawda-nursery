<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Invoice;
use App\Models\TenantSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SetupController extends Controller
{
    private function tenant(Request $r)
    {
        abort_unless($r->user()->role === 'owner', 403);

        return $r->user()->tenant;
    }

    private function progress()
    {
        return ['rooms' => Classroom::count(), 'children' => Child::where('status', 'active')->count(), 'guardian_gaps' => Child::where('status', 'active')->whereDoesntHave('guardians')->count(), 'pickup_gaps' => Child::where('status', 'active')->whereDoesntHave('activePickupPersons')->count()];
    }

    public function index(Request $r)
    {
        $t = $this->tenant($r);

        return Inertia::render('Setup/Index', ['nursery' => $t->only('name', 'timezone', 'currency', 'setup_step', 'setup_completed_at'), 'progress' => $this->progress(), 'financial_locked' => Invoice::exists(), 'dates_locked' => Attendance::exists() || DailyLog::exists(), 'writable' => TenantSubscription::forTenant($t->id)?->writable() ?? true]);
    }

    public function update(Request $r)
    {
        $t = $this->tenant($r);
        $d = $r->validate(['action' => ['required', Rule::in(['details', 'step', 'later', 'finish'])], 'step' => 'nullable|integer|min:0|max:4']);
        if ($d['action'] === 'details') {
            $v = $r->validate(['name' => 'required|string|max:180', 'timezone' => ['required', Rule::in(array_unique(['Africa/Cairo', 'Asia/Riyadh', 'Asia/Dubai', 'Asia/Kuwait', 'Asia/Qatar', 'Asia/Bahrain', 'Asia/Muscat', $t->timezone]))], 'currency' => ['required', Rule::in(array_unique(['EGP', 'SAR', 'AED', 'QAR', $t->currency]))]]);
            DB::transaction(function () use ($t, $v) {
                $t->newQuery()->whereKey($t->id)->lockForUpdate()->firstOrFail();
                if ($v['currency'] !== $t->currency && Invoice::exists()) {
                    throw ValidationException::withMessages(['currency' => 'لا يمكن تغيير عملة سجلات الرسوم الحالية من دليل الإعداد.']);
                }
                if ($v['timezone'] !== $t->timezone && (Attendance::exists() || DailyLog::exists())) {
                    throw ValidationException::withMessages(['timezone' => 'لا يمكن تغيير توقيت سجل الحضور والرعاية الحالي من دليل الإعداد.']);
                }
                $t->fill($v);
                $t->setup_step = 1;
                $t->save();
            });
        } elseif ($d['action'] === 'finish') {
            $p = $this->progress();
            if (! $p['rooms'] || ! $p['children'] || $p['guardian_gaps'] || $p['pickup_gaps']) {
                throw ValidationException::withMessages(['setup' => 'أضف فصلاً وطفلاً، واربط ولي الأمر وأكمل قائمة الاستلام لكل طفل نشط أولاً.']);
            }
            $r->validate(['safety_review' => 'accepted']);
            $t->forceFill(['setup_completed_at' => now(), 'setup_pending' => false, 'setup_step' => 4])->save();

            return redirect()->route('dashboard')->with('success', 'اكتمل دليل الإعداد. يمكنك العودة إليه من القائمة.');
        } elseif ($d['action'] === 'later') {
            $t->forceFill(['setup_pending' => false])->save();

            return redirect()->route('dashboard');
        } else {
            $t->forceFill(['setup_step' => $d['step'] ?? 0])->save();
        }

        return back();
    }
}
