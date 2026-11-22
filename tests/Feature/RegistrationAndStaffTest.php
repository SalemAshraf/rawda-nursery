<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAndStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_nursery_and_owner(): void
    {
        $this->post(route('register'), [
            'nursery_name' => 'Little Stars',
            'timezone' => 'Africa/Cairo',
            'currency' => 'EGP',
            'name' => 'Nour Owner',
            'email' => 'nour@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $tenant = Tenant::where('name', 'Little Stars')->first();
        $this->assertNotNull($tenant);
        $owner = User::where('email', 'nour@example.test')->first();
        $this->assertSame($tenant->id, $owner->tenant_id);
        $this->assertSame('owner', $owner->role);
    }

    public function test_only_owner_manages_staff(): void
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser($tenant, 'owner');
        $manager = $this->createUser($tenant, 'manager');

        $this->actingAs($manager)->get(route('staff.index'))->assertForbidden();

        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Teacher One',
            'email' => 'teacher1@example.test',
            'role' => 'teacher',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();

        $teacher = User::where('email', 'teacher1@example.test')->first();
        $this->assertSame($tenant->id, $teacher->tenant_id);
        $this->assertSame('teacher', $teacher->role);

        // Owners cannot create a second owner through the staff screen.
        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Boss Two',
            'email' => 'boss2@example.test',
            'role' => 'owner',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('role');
    }
}
