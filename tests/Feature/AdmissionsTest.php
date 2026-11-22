<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdmissionsTest extends TestCase
{
    use RefreshDatabase;

    private function inquiryData(array $extra = []): array
    {
        return array_merge(['first_name' => 'New', 'last_name' => 'Child', 'dob' => '2023-05-10', 'guardian_name' => 'Parent One', 'guardian_phone' => '01000000000', 'guardian_relation' => 'mother', 'status' => 'waitlist', 'follow_up_on' => '2026-11-25', 'notes' => 'Synthetic inquiry'], $extra);
    }

    public function test_managers_can_track_inquiries_but_teachers_cannot_see_prospect_contacts(): void
    {
        $t = $this->createTenant();
        $m = $this->createUser($t, 'manager');
        $teacher = $this->createUser($t, 'teacher');
        $this->actingAs($m)->post(route('admissions.store'), $this->inquiryData())->assertRedirect();
        $this->assertDatabaseHas('admissions', ['tenant_id' => $t->id, 'status' => 'waitlist', 'guardian_name' => 'Parent One']);
        $this->get(route('admissions.index'))->assertInertia(fn (Assert $p) => $p->where('rows.total', 1)->where('rows.data.0.status', 'waitlist'));
        $this->actingAs($teacher)->get(route('admissions.index'))->assertForbidden();
        $this->post(route('admissions.store'), $this->inquiryData())->assertForbidden();
    }

    public function test_enrollment_creates_one_child_and_guardian_but_never_pickup_permission_or_invoice(): void
    {
        $t = $this->createTenant();
        $m = $this->createUser($t, 'manager');
        $room = Classroom::create(['tenant_id' => $t->id, 'name' => 'Room', 'capacity' => 10]);
        $a = Admission::create($this->inquiryData(['tenant_id' => $t->id, 'classroom_id' => $room->id]));
        $this->actingAs($m)->post(route('admissions.enroll', $a), ['classroom_id' => $room->id, 'confirmed' => true])->assertRedirect();
        $a->refresh();
        $this->assertNotNull($a->child_id);
        $this->assertSame('enrolled', $a->status);
        $this->assertNull($a->follow_up_on);
        $this->assertDatabaseHas('children', ['id' => $a->child_id, 'tenant_id' => $t->id, 'classroom_id' => $room->id]);
        $this->assertDatabaseHas('guardians', ['tenant_id' => $t->id, 'phone' => '01000000000']);
        $this->assertDatabaseHas('child_guardian', ['child_id' => $a->child_id, 'is_primary' => true]);
        $this->assertDatabaseCount('pickup_persons', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->post(route('admissions.enroll', $a), ['classroom_id' => $room->id, 'confirmed' => true])->assertRedirect(route('children.show', $a->child_id));
        $this->assertDatabaseCount('children', 1);
        $this->assertDatabaseCount('guardians', 1);
        $this->put(route('admissions.update', $a), $this->inquiryData())->assertSessionHasErrors('status');
    }

    public function test_tenant_boundary_holds_for_search_classroom_assignment_and_enrollment(): void
    {
        $t = $this->createTenant();
        $m = $this->createUser($t);
        $other = $this->createTenant('Other');
        $room = Classroom::create(['tenant_id' => $other->id, 'name' => 'Other room']);
        $a = Admission::create($this->inquiryData(['tenant_id' => $other->id]));
        $this->actingAs($m)->get(route('admissions.index'))->assertInertia(fn (Assert $p) => $p->where('rows.total', 0)->has('classrooms', 0));
        $this->post(route('admissions.store'), $this->inquiryData(['classroom_id' => $room->id]))->assertNotFound();
        $this->put(route('admissions.update', $a), $this->inquiryData())->assertNotFound();
        $this->post(route('admissions.enroll', $a), ['classroom_id' => $room->id, 'confirmed' => true])->assertNotFound();
        $this->assertDatabaseCount('children', 0);
    }

    public function test_closed_or_unconfirmed_inquiries_cannot_be_enrolled_and_stage_cannot_fake_enrollment(): void
    {
        $t = $this->createTenant();
        $m = $this->createUser($t);
        $room = Classroom::create(['tenant_id' => $t->id, 'name' => 'Room']);
        $a = Admission::create($this->inquiryData(['tenant_id' => $t->id, 'status' => 'closed']));
        $this->actingAs($m)->post(route('admissions.enroll', $a), ['classroom_id' => $room->id, 'confirmed' => false])->assertSessionHasErrors('confirmed');
        $this->post(route('admissions.enroll', $a), ['classroom_id' => $room->id, 'confirmed' => true])->assertSessionHasErrors('status');
        $this->put(route('admissions.update', $a), $this->inquiryData(['status' => 'enrolled']))->assertSessionHasErrors('status');
        $this->assertDatabaseCount('children', 0);
    }

    public function test_foreign_classroom_is_rejected_even_for_own_inquiry(): void
    {
        $t = $this->createTenant();
        $m = $this->createUser($t);
        $other = $this->createTenant('Other');
        $room = Classroom::create(['tenant_id' => $other->id, 'name' => 'Other room']);
        $a = Admission::create($this->inquiryData(['tenant_id' => $t->id]));
        $this->actingAs($m)->post(route('admissions.enroll', $a), ['classroom_id' => $room->id, 'confirmed' => true])->assertNotFound();
        $this->assertDatabaseCount('children',0);
        $this->assertNull($a->fresh()->child_id);
    }
}
