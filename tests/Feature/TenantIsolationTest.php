<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_of_another_tenant_is_not_visible_or_reachable(): void
    {
        $tenantA = $this->createTenant('Nursery A');
        $tenantB = $this->createTenant('Nursery B');
        $ownerA = $this->createUser($tenantA, 'owner');
        $this->createUser($tenantB, 'owner');

        $childB = Child::withoutTenantScope(fn () => Child::create([
            'tenant_id' => $tenantB->id,
            'first_name' => 'Mariam',
            'last_name' => 'Other',
            'status' => 'active',
        ]));

        $this->actingAs($ownerA)
            ->get(route('children.show', $childB->id))
            ->assertNotFound();

        $this->actingAs($ownerA)
            ->put(route('children.update', $childB->id), [
                'first_name' => 'Hacked', 'last_name' => 'Name', 'status' => 'active',
            ])->assertNotFound();

        $this->actingAs($ownerA)
            ->get(route('children.index'))
            ->assertOk()
            ->assertDontSee('Mariam');
    }

    public function test_child_cannot_be_assigned_to_another_tenants_classroom(): void
    {
        $tenantA = $this->createTenant('Nursery A');
        $tenantB = $this->createTenant('Nursery B');
        $ownerA = $this->createUser($tenantA, 'owner');

        $classroomB = Classroom::withoutTenantScope(fn () => Classroom::create([
            'tenant_id' => $tenantB->id, 'name' => 'B Room',
        ]));

        $this->actingAs($ownerA)
            ->post(route('children.store'), [
                'first_name' => 'Ali', 'last_name' => 'Test', 'classroom_id' => $classroomB->id,
            ])->assertStatus(422);
    }

    public function test_invoice_for_another_tenants_child_is_not_found(): void
    {
        $tenantA = $this->createTenant('Nursery A');
        $tenantB = $this->createTenant('Nursery B');
        $ownerA = $this->createUser($tenantA, 'owner');

        $childB = Child::withoutTenantScope(fn () => Child::create([
            'tenant_id' => $tenantB->id, 'first_name' => 'Omar', 'last_name' => 'B', 'status' => 'active',
        ]));

        $this->actingAs($ownerA)
            ->post(route('billing.invoices.store'), [
                'child_id' => $childB->id,
                'issue_date' => '2026-11-01',
                'due_date' => '2026-11-10',
                'amount' => '1000',
            ])->assertNotFound();
    }
}
