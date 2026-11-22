<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('manage-nursery');
        $tenant = $request->user()->tenant;
        $today = CarbonImmutable::parse($tenant->today(), $tenant->timezone);
        $data = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $from = CarbonImmutable::parse($data['from'] ?? $today->subDays(29)->toDateString(), $tenant->timezone)->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? $today->toDateString(), $tenant->timezone)->startOfDay();
        throw_if($to->lt($from) || $from->diffInDays($to) > 365, ValidationException::withMessages(['from' => 'Choose a range of at most 366 days in chronological order.']));
        $attendance = Attendance::whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())->get();
        $logs = DailyLog::whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())->get(['id', 'date']);
        $payments = Payment::whereDate('paid_at', '>=', $from->toDateString())->whereDate('paid_at', '<=', $to->toDateString())->get();
        $issued = Invoice::whereDate('issue_date', '>=', $from->toDateString())->whereDate('issue_date', '<=', $to->toDateString())->whereIn('status', ['sent', 'paid'])->sum('amount_minor');
        $outstanding = Invoice::where('status', 'sent')->withSum('payments', 'amount_minor')->get()->sum(fn ($i) => max(0, $i->amount_minor - (int) $i->payments_sum_amount_minor));
        $attendanceByDay = $attendance->groupBy(fn ($a) => $a->date->toDateString());
        $logsByDay = $logs->groupBy(fn ($l) => $l->date->toDateString());
        $paymentsByDay = $payments->groupBy(fn ($p) => $p->paid_at->toDateString());
        $daily = [];
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $key = $date->toDateString();
            $rows = $attendanceByDay->get($key, collect());
            $daily[] = ['date' => $key, 'arrivals' => $rows->whereNotNull('check_in_at')->count(), 'departures' => $rows->whereNotNull('check_out_at')->count(),
                'care_logs' => $logsByDay->get($key, collect())->count(), 'collected_minor' => (int) $paymentsByDay->get($key, collect())->sum('amount_minor')];
        }

        return Inertia::render('Reports/Index', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'currency' => $tenant->currency, 'daily' => $daily,
            'summary' => ['arrivals' => $attendance->whereNotNull('check_in_at')->count(), 'care_logs' => $logs->count(),
                'collected' => Money::format((int) $payments->sum('amount_minor'), $tenant->currency), 'issued' => Money::format((int) $issued, $tenant->currency),
                'outstanding_now' => Money::format((int) $outstanding, $tenant->currency)],
            'methods' => $payments->groupBy('method')->map(fn ($rows, $method) => ['method' => $method, 'amount' => Money::format((int) $rows->sum('amount_minor'), $tenant->currency), 'count' => $rows->count()])->values(),
            'rooms' => Classroom::withCount(['children' => fn ($q) => $q->where('status','active')])->orderBy('name')->get(['id', 'name', 'capacity']),
        ]);
    }
}
