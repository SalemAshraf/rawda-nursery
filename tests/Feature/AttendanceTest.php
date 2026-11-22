<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\PickupPerson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function childOf($tenant): Child
    {
        return Child::withoutTenantScope(fn () => Child::create([
            'tenant_id' => $tenant->id, 'first_name' => 'Laila', 'last_name' => 'N', 'status' => 'active',
        ]));
    }

    public function test_check_in_then_checkout_to_authorized_pickup(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = $this->childOf($tenant);
        $person = PickupPerson::withoutTenantScope(fn () => PickupPerson::create([
            'tenant_id' => $tenant->id, 'child_id' => $child->id,
            'name' => 'Mona Aunt', 'relation' => 'aunt', 'active' => true,
        ]));

        $this->actingAs($teacher)->post(route('attendance.check-in', $child))->assertRedirect();
        $this->actingAs($teacher)->post(route('attendance.check-out', $child), [
            'pickup_person_id' => $person->id,
        ])->assertSessionHasNoErrors();

        $attendance = Attendance::withoutGlobalScopes()->first();
        $this->assertNotNull($attendance->check_in_at);
        $this->assertNotNull($attendance->check_out_at);
        $this->assertSame($person->id, $attendance->pickup_person_id);
    }

    public function test_checkout_rejected_without_authorized_pickup_person(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = $this->childOf($tenant);

        $this->actingAs($teacher)->post(route('attendance.check-in', $child));
        $this->actingAs($teacher)->post(route('attendance.check-out', $child), [
            'pickup_person_id' => 999,
        ])->assertSessionHasErrors('pickup_person_id');

        $this->assertNull(Attendance::withoutGlobalScopes()->first()->check_out_at);
    }

    public function test_checkout_rejected_to_pickup_person_of_another_child(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = $this->childOf($tenant);
        $other = Child::withoutTenantScope(fn () => Child::create([
            'tenant_id' => $tenant->id, 'first_name' => 'Other', 'last_name' => 'Kid', 'status' => 'active',
        ]));
        $person = PickupPerson::withoutTenantScope(fn () => PickupPerson::create([
            'tenant_id' => $tenant->id, 'child_id' => $other->id,
            'name' => 'Other Dad', 'relation' => 'father', 'active' => true,
        ]));

        $this->actingAs($teacher)->post(route('attendance.check-in', $child));
        $this->actingAs($teacher)->post(route('attendance.check-out', $child), [
            'pickup_person_id' => $person->id,
        ])->assertSessionHasErrors('pickup_person_id');
    }

    public function test_checkout_rejected_to_deactivated_pickup_person(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = $this->childOf($tenant);
        $person = PickupPerson::withoutTenantScope(fn () => PickupPerson::create([
            'tenant_id' => $tenant->id, 'child_id' => $child->id,
            'name' => 'Old Driver', 'relation' => 'driver', 'active' => false,
        ]));

        $this->actingAs($teacher)->post(route('attendance.check-in', $child));
        $this->actingAs($teacher)->post(route('attendance.check-out', $child), [
            'pickup_person_id' => $person->id,
        ])->assertSessionHasErrors('pickup_person_id');
    }

    public function test_checkout_rejected_when_not_checked_in_or_already_out(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = $this->childOf($tenant);
        $person = PickupPerson::withoutTenantScope(fn () => PickupPerson::create([
            'tenant_id' => $tenant->id, 'child_id' => $child->id,
            'name' => 'Mom', 'relation' => 'mother', 'active' => true,
        ]));

        // Not checked in yet.
        $this->actingAs($teacher)->post(route('attendance.check-out', $child), [
            'pickup_person_id' => $person->id,
        ])->assertSessionHasErrors();

        $this->actingAs($teacher)->post(route('attendance.check-in', $child));
        $this->actingAs($teacher)->post(route('attendance.check-out', $child), [
            'pickup_person_id' => $person->id,
        ])->assertSessionHasNoErrors();

        // Second checkout of the same day.
        $this->actingAs($teacher)->post(route('attendance.check-out', $child), [
            'pickup_person_id' => $person->id,
        ])->assertSessionHasErrors();
    }
}
