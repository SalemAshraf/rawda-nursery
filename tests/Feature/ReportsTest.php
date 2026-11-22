<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\DailyLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_range_metrics_exclude_other_nurseries_and_drafts_but_current_debt_is_explicit(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $other = $this->createTenant('Other');
        $c = Child::create(['tenant_id' => $t->id, 'first_name' => 'Test', 'last_name' => 'Child', 'status' => 'active']);
        $i = Invoice::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'issue_date' => '2026-11-22', 'due_date' => '2026-11-22', 'amount_minor' => 10000, 'status' => 'sent', 'created_by' => $u->id]);
        Invoice::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'issue_date' => '2026-11-22', 'due_date' => '2026-11-22', 'amount_minor' => 90000, 'status' => 'draft', 'created_by' => $u->id]);
        Payment::create(['tenant_id' => $t->id, 'invoice_id' => $i->id, 'paid_at' => '2026-11-22 23:59:59', 'amount_minor' => 4000, 'method' => 'cash', 'recorded_by' => $u->id]);
        Payment::create(['tenant_id' => $other->id, 'invoice_id' => $i->id, 'paid_at' => '2026-11-22 12:00:00', 'amount_minor' => 99999, 'method' => 'cash', 'recorded_by' => $u->id]);
        Attendance::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'date' => '2026-11-22', 'check_in_at' => '2026-11-22 08:00:00', 'checked_in_by' => $u->id]);
        DailyLog::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'date' => '2026-11-22', 'meals' => 'Lunch', 'created_by' => $u->id]);
        $this->actingAs($u)->get(route('reports', ['from' => '2026-11-22', 'to' => '2026-11-22']))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('summary.collected', '40.00 EGP')->where('summary.issued', '100.00 EGP')->where('summary.outstanding_now', '60.00 EGP')
            ->where('summary.arrivals', 1)->where('summary.care_logs', 1)->has('daily', 1)->where('daily.0.collected_minor', 4000));
    }

    public function test_reports_are_manager_only_and_reject_unbounded_ranges(): void
    {
        $t = $this->createTenant();
        $teacher = $this->createUser($t, 'teacher');
        $owner = $this->createUser($t);
        $this->actingAs($teacher)->get(route('reports'))->assertForbidden();
        $this->actingAs($owner)->get(route('reports', ['from' => '2020-01-01', 'to' => '2026-11-22']))->assertSessionHasErrors('from');
        $this->get(route('reports', ['from' => '2026-11-22', 'to' => '2026-11-21']))->assertSessionHasErrors('to');
    }
}
