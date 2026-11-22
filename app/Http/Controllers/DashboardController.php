<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Incident;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $today = $tenant->today();

        $activeChildren = Child::where('status', 'active')->count();
        $presentToday = Attendance::where('date', $today)->whereNotNull('check_in_at')->count();
        $checkedOutToday = Attendance::where('date', $today)->whereNotNull('check_out_at')->count();

        $openInvoices = Invoice::where('status', 'sent')->get();
        $outstandingMinor = $openInvoices->sum(fn (Invoice $i) => $i->outstandingMinor());
        $overdueCount = $openInvoices->filter(fn (Invoice $i) => $i->isOverdue($today))->count();

        $unacknowledgedIncidents = Incident::whereNull('guardian_acknowledged_at')->count();

        return Inertia::render('Dashboard', [
            'today' => $today,
            'stats' => [
                'active_children' => $activeChildren,
                'present_today' => $presentToday,
                'checked_out_today' => $checkedOutToday,
                'outstanding' => Money::format($outstandingMinor, $tenant->currency),
                'overdue_invoices' => $overdueCount,
                'unacknowledged_incidents' => $unacknowledgedIncidents,
            ],
        ]);
    }
}
