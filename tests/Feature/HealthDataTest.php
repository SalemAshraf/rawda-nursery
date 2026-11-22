<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_allergies_but_not_health_notes(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');
        $child = Child::withoutTenantScope(fn () => Child::create([
            'tenant_id' => $tenant->id, 'first_name' => 'Salma', 'last_name' => 'R',
            'status' => 'active', 'allergies' => 'peanuts', 'health_notes' => 'weekly physiotherapy',
        ]));

        $this->actingAs($teacher)
            ->get(route('children.show', $child))
            ->assertInertia(fn (Assert $page) => $page
                ->where('child.allergies', 'peanuts')
                ->where('child.health_notes', null)
                ->where('canSeeHealth', false)
            );
    }

    public function test_manager_sees_health_notes(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createUser($tenant, 'manager');
        $child = Child::withoutTenantScope(fn () => Child::create([
            'tenant_id' => $tenant->id, 'first_name' => 'Salma', 'last_name' => 'R',
            'status' => 'active', 'health_notes' => 'weekly physiotherapy',
        ]));

        $this->actingAs($manager)
            ->get(route('children.show', $child))
            ->assertInertia(fn (Assert $page) => $page
                ->where('child.health_notes', 'weekly physiotherapy')
                ->where('canSeeHealth', true)
            );
    }

    public function test_health_data_changes_are_audited(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createUser($tenant, 'manager');

        $this->actingAs($manager)->post(route('children.store'), [
            'first_name' => 'Adam', 'last_name' => 'S', 'allergies' => 'lactose', 'health_notes' => 'note',
        ]);
        $child = Child::withoutGlobalScopes()->first();

        $this->actingAs($manager)->put(route('children.update', $child), [
            'first_name' => 'Adam', 'last_name' => 'S', 'status' => 'active',
            'allergies' => 'lactose, eggs', 'health_notes' => 'note',
        ]);

        $actions = AuditLog::withoutGlobalScopes()->pluck('action')->all();
        $this->assertContains('set-health-data', $actions);
        $this->assertContains('update-health-data', $actions);
    }
}
