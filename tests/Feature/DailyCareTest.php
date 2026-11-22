<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DailyCareTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_log_keeps_all_fields_on_selected_date_and_can_be_updated(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = Child::create(['tenant_id' => $tenant->id, 'first_name' => 'Daily', 'last_name' => 'Care', 'status' => 'active']);
        $data = ['date' => '2026-11-20', 'meals' => 'Lunch eaten', 'naps' => '13:00–14:00', 'mood' => 'Happy', 'activities' => 'Painting', 'notes' => 'Good day'];
        $this->actingAs($teacher)->post(route('logs.upsert', $child), $data)->assertRedirect(route('logs.index', ['date' => '2026-11-20']));
        $this->assertDatabaseHas('daily_logs', ['child_id' => $child->id, 'date' => '2026-11-20', 'meals' => 'Lunch eaten', 'notes' => 'Good day']);
        $this->post(route('logs.upsert', $child), array_merge($data, ['meals' => 'Updated lunch']))->assertRedirect();
        $this->assertDatabaseCount('daily_logs', 1);
        $this->assertDatabaseHas('daily_logs', ['meals' => 'Updated lunch']);
        $this->get(route('logs.index', ['date' => '2026-11-20']))->assertOk()->assertInertia(fn (Assert $p) => $p->where('date', '2026-11-20')->where('children.0.log.meals', 'Updated lunch'));
    }

    public function test_edit_profile_returns_the_existing_room_and_only_current_nursery_rooms(): void
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser($tenant);
        $other = $this->createTenant('Other');
        $room = Classroom::create(['tenant_id' => $tenant->id, 'name' => 'Current room']);
        Classroom::create(['tenant_id' => $other->id, 'name' => 'Private room']);
        $child = Child::create(['tenant_id' => $tenant->id, 'classroom_id' => $room->id, 'first_name' => 'Child', 'last_name' => 'One', 'status' => 'active']);
        $this->actingAs($owner)->get(route('children.show', $child))->assertInertia(fn (Assert $p) => $p->where('child.classroom_id', $room->id)->has('classrooms', 1)->where('classrooms.0.id', $room->id));
    }

    public function test_teacher_cannot_write_a_log_for_another_nurserys_child(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $other = $this->createTenant('Other');
        $child = Child::create(['tenant_id' => $other->id, 'first_name' => 'Other', 'last_name' => 'Child', 'status' => 'active']);
        $this->actingAs($teacher)->post(route('logs.upsert', $child), ['meals' => 'Bad write'])->assertNotFound();
        $this->assertDatabaseCount('daily_logs',0);
    }
}
