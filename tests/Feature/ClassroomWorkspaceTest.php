<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassroomWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_classroom_workspace_counts_only_active_children_and_current_nursery(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $other = $this->createTenant('Other');
        $r = Classroom::create(['tenant_id' => $t->id, 'name' => 'Our room', 'capacity' => 12]);
        Classroom::create(['tenant_id' => $other->id, 'name' => 'Private room', 'capacity' => 99]);
        foreach (['active', 'archived'] as $status) {
            Child::create(['tenant_id' => $t->id, 'classroom_id' => $r->id, 'first_name' => 'Child', 'last_name' => $status, 'status' => $status]);
        }
        $this->actingAs($u)->get(route('classrooms.index'))->assertInertia(fn (Assert $p) => $p->has('classrooms', 1)->where('classrooms.0.children_count', 1)->where('classrooms.0.capacity', 12));
    }

    public function test_only_managers_can_edit_their_own_classrooms_and_changes_are_audited(): void
    {
        $t = $this->createTenant();
        $teacher = $this->createUser($t, 'teacher');
        $manager = $this->createUser($t, 'manager');
        $other = $this->createTenant('Other');
        $r = Classroom::create(['tenant_id' => $t->id, 'name' => 'Our room', 'capacity' => 12]);
        $foreign = Classroom::create(['tenant_id' => $other->id, 'name' => 'Private room']);
        $data = ['name' => 'Updated room', 'capacity' => 16];
        $this->actingAs($teacher)->put(route('classrooms.update', $r), $data)->assertForbidden();
        $this->actingAs($manager)->put(route('classrooms.update', $foreign), $data)->assertNotFound();
        $this->put(route('classrooms.update', $r), $data)->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', ['id' => $r->id, 'capacity' => 16]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'update', 'entity' => 'classroom', 'entity_id' => $r->id]);
    }
}
