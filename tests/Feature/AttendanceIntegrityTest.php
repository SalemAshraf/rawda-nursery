<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_check_in_keeps_one_attendance_and_one_audit_record(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t, 'teacher');
        $c = Child::create(['tenant_id' => $t->id, 'first_name' => 'Test', 'last_name' => 'Child', 'status' => 'active']);
        $this->actingAs($u)->post(route('attendance.check-in', $c))->assertRedirect();
        $first = Attendance::first()->check_in_at;
        $this->post(route('attendance.check-in', $c))->assertRedirect();
        $this->assertDatabaseCount('attendance', 1);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertTrue($first->equalTo(Attendance::first()->check_in_at));
    }

    public function test_archived_child_cannot_be_checked_in(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t, 'teacher');
        $c = Child::create(['tenant_id' => $t->id, 'first_name' => 'Test', 'last_name' => 'Child', 'status' => 'archived']);
        $this->actingAs($u)->post(route('attendance.check-in', $c))->assertStatus(422);
        $this->assertDatabaseCount('attendance', 0);
    }
}
