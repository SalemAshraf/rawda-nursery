<?php

namespace Tests\Feature;

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_nursery_owner_cannot_delete_their_login(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $this->actingAs($u)->from(route('profile.edit'))->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('password');
        $this->assertAuthenticatedAs($u);
        $this->assertNotNull($u->fresh());
    }

    public function test_staff_login_deletion_preserves_nursery_and_child_records(): void
    {
        $t = $this->createTenant();
        $this->createUser($t);
        $u = $this->createUser($t, 'teacher');
        $c = Child::create(['tenant_id' => $t->id, 'first_name' => 'Child', 'last_name' => 'One', 'status' => 'active']);
        $this->actingAs($u)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertGuest();
        $this->assertNull($u->fresh());
        $this->assertDatabaseHas('tenants', ['id' => $t->id]);
        $this->assertDatabaseHas('children', ['id' => $c->id]);
    }
}
