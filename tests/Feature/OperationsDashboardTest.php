<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperationsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_tracks_current_care_and_keeps_other_nurseries_out(): void
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser($tenant);
        $other = $this->createTenant('Other nursery');
        $room = Classroom::create(['tenant_id' => $tenant->id, 'name' => 'Butterflies', 'capacity' => 10]);
        $a = Child::create(['tenant_id' => $tenant->id, 'classroom_id' => $room->id, 'first_name' => 'Present', 'last_name' => 'Child', 'status' => 'active']);
        $b = Child::create(['tenant_id' => $tenant->id, 'classroom_id' => $room->id, 'first_name' => 'Collected', 'last_name' => 'Child', 'status' => 'active']);
        Child::create(['tenant_id' => $tenant->id, 'first_name' => 'Absent', 'last_name' => 'Child', 'status' => 'active']);
        Child::create(['tenant_id' => $other->id, 'first_name' => 'Private', 'last_name' => 'Other', 'status' => 'active']);
        Attendance::create(['tenant_id' => $tenant->id, 'child_id' => $a->id, 'date' => $tenant->today(), 'check_in_at' => now(), 'checked_in_by' => $owner->id]);
        Attendance::create(['tenant_id' => $tenant->id, 'child_id' => $b->id, 'date' => $tenant->today(), 'check_in_at' => now(), 'check_out_at' => now(), 'checked_in_by' => $owner->id, 'checked_out_by' => $owner->id]);
        DailyLog::create(['tenant_id' => $tenant->id, 'child_id' => $b->id, 'date' => $tenant->today(), 'created_by' => $owner->id, 'meals' => 'Lunch']);
        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')->where('stats.active_children', 3)->where('stats.on_site', 1)
            ->where('stats.present_today', 2)->where('stats.checked_out_today', 1)->where('stats.not_arrived', 1)
            ->where('stats.missing_logs', 1)->where('stats.missing_pickup', 3)->has('register', 3)
            ->where('classrooms.0.enrolled', 2)->where('classrooms.0.on_site', 1));
    }

    public function test_teacher_dashboard_never_receives_financial_totals_or_medical_notes(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = Child::create(['tenant_id' => $tenant->id, 'first_name' => 'Care', 'last_name' => 'Child', 'status' => 'active', 'health_notes' => 'Restricted medical history', 'allergies' => 'Nuts']);
        Invoice::create(['tenant_id' => $tenant->id, 'child_id' => $child->id, 'issue_date' => $tenant->today(), 'due_date' => $tenant->today(), 'amount_minor' => 12345, 'status' => 'sent', 'created_by' => $teacher->id]);
        $this->actingAs($teacher)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('canManage', false)->missing('stats.outstanding')->missing('stats.overdue_invoices')
            ->where('register.0.has_allergies', true)->missing('register.0.health_notes')->missing('register.0.allergies'));
    }
}
