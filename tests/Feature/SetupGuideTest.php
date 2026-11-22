<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Classroom;
use App\Models\Invoice;
use App\Models\PickupPerson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SetupGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_workspace_guide_redirect_resumes_and_can_be_deferred_without_resetting_data(): void
    {
        $t = $this->createTenant();
        $t->forceFill(['setup_pending' => true])->save();
        $u = $this->createUser($t);
        $this->actingAs($u)->get(route('dashboard'))->assertRedirect(route('setup.index'));
        $this->post(route('setup.update'), ['action' => 'step', 'step' => 3])->assertRedirect();
        $this->get(route('setup.index'))->assertInertia(fn (Assert $p) => $p->where('nursery.setup_step', 3));
        $this->post(route('setup.update'), ['action' => 'later'])->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame(3, $t->fresh()->setup_step);
    }

    public function test_guide_is_owner_only_and_rejects_invalid_steps(): void
    {
        $t = $this->createTenant();
        $this->actingAs($this->createUser($t, 'teacher'))->get(route('setup.index'))->assertForbidden();
        $this->post(route('setup.update'), ['action' => 'later'])->assertForbidden();
        $this->actingAs($this->createUser($t))->post(route('setup.update'), ['action' => 'step', 'step' => 100])->assertSessionHasErrors('step');
    }

    public function test_completion_checks_real_records_and_pickup_permissions_and_can_be_revisited(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $this->actingAs($u);
        $this->post(route('setup.update'), ['action' => 'finish', 'safety_review' => true])->assertSessionHasErrors('setup');
        $room = Classroom::create(['name' => 'Test room', 'capacity' => 12]);
        $c = Child::create(['first_name' => 'Test', 'last_name' => 'Child', 'classroom_id' => $room->id, 'status' => 'active']);
        $this->post(route('setup.update'), ['action' => 'finish', 'safety_review' => true])->assertSessionHasErrors('setup');
        $g=\App\Models\Guardian::create(['name'=>'Test guardian','relation'=>'parent']);$c->guardians()->attach($g->id,['is_primary'=>true]);
        PickupPerson::create(['child_id' => $c->id, 'name' => 'Test pickup', 'relation' => 'guardian', 'active' => true]);
        $this->post(route('setup.update'), ['action' => 'finish', 'safety_review' => true])->assertRedirect(route('dashboard'));
        $this->assertNotNull($t->fresh()->setup_completed_at);
        $this->get(route('setup.index'))->assertOk();
        $this->assertDatabaseCount('children', 1);
    }

    public function test_existing_fee_currency_cannot_be_reinterpreted_by_setup(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $this->actingAs($u);
        $c = Child::create(['first_name' => 'Test', 'last_name' => 'Child', 'status' => 'active']);
        Invoice::create(['child_id' => $c->id, 'issue_date' => '2026-11-22', 'due_date' => '2026-11-22', 'amount_minor' => 10000, 'status' => 'draft', 'created_by' => $u->id]);
        $this->post(route('setup.update'), ['action' => 'details', 'name' => $t->name, 'timezone' => $t->timezone, 'currency' => 'SAR'])->assertSessionHasErrors('currency');
        $this->assertSame('EGP', $t->fresh()->currency);
    }
}
