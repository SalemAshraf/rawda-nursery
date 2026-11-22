<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Incident;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $tenant = $request->user()->tenant;
        if ($request->user()->role === 'owner' && $tenant->setup_pending && !$tenant->setup_completed_at) return redirect()->route('setup.index');
        $today = $tenant->today();
        $canManage = Gate::allows('manage-nursery');
        $children = Child::with('classroom')->withCount('activePickupPersons')->where('status', 'active')->orderBy('first_name')->get();
        $attendance = Attendance::whereDate('date', $today)->get()->keyBy('child_id');
        $logs = DailyLog::whereDate('date', $today)->pluck('child_id');
        $present = $attendance->filter(fn ($a) => $a->check_in_at && ! $a->check_out_at);
        $pendingIncidents = Incident::with('child')->whereNull('guardian_acknowledged_at')->orderByDesc('occurred_at')->get();

        $stats = [
            'active_children' => $children->count(),
            'present_today' => $attendance->whereNotNull('check_in_at')->count(),
            'on_site' => $present->count(),
            'checked_out_today' => $attendance->whereNotNull('check_out_at')->count(),
            'not_arrived' => $children->filter(fn ($c) => ! $attendance->get($c->id)?->check_in_at)->count(),
            'missing_logs' => $children->filter(fn ($c) => $attendance->get($c->id)?->check_in_at && ! $logs->contains($c->id))->count(),
            'missing_pickup' => $children->where('active_pickup_persons_count', 0)->count(),
            'unacknowledged_incidents' => $pendingIncidents->count(),
        ];
        $overdue = collect();
        if ($canManage) {
            $invoices = Invoice::with('child')->withSum('payments', 'amount_minor')->where('status', 'sent')->get();
            $balance = fn ($i) => max(0, $i->amount_minor - (int) $i->payments_sum_amount_minor);
            $overdue = $invoices->filter(fn ($i) => $i->due_date->toDateString() < $today && $balance($i) > 0);
            $stats['outstanding'] = Money::format($invoices->sum($balance), $tenant->currency);
            $stats['overdue_invoices'] = $overdue->count();
        }

        return Inertia::render('Dashboard', [
            'today' => $today,
            'stats' => $stats,
            'canManage' => $canManage,
            'classrooms' => Classroom::orderBy('name')->get()->map(fn ($room) => [
                'id' => $room->id, 'name' => $room->name, 'capacity' => $room->capacity,
                'enrolled' => $children->where('classroom_id', $room->id)->count(),
                'on_site' => $children->where('classroom_id', $room->id)->filter(fn ($c) => $present->has($c->id))->count(),
            ]),
            'register' => $children->map(function ($c) use ($attendance, $logs) {
                $a = $attendance->get($c->id);

                return [
                    'id' => $c->id, 'name' => $c->fullName(), 'classroom' => $c->classroom?->name,
                    'status' => $a?->check_out_at ? 'collected' : ($a?->check_in_at ? 'on_site' : 'not_arrived'),
                    'has_allergies' => trim((string) $c->allergies) !== '',
                    'has_pickup' => $c->active_pickup_persons_count > 0,
                    'has_log' => $logs->contains($c->id),
                ];
            }),
            'pendingIncidents' => $pendingIncidents->take(5)->map(fn ($i) => [
                'id' => $i->id, 'child_id' => $i->child_id, 'name' => $i->child?->fullName(),
                'severity' => $i->severity, 'occurred_at' => $i->occurred_at->toDateString(),
            ])->values(),
        ]);
    }
}
