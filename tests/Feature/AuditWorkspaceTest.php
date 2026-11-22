<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_is_manager_only_and_tenant_scoped(): void
    {
        $t = $this->createTenant();
        $owner = $this->createUser($t);
        $teacher = $this->createUser($t, 'teacher');
        $other = $this->createTenant('Other');
        AuditLog::create(['tenant_id' => $t->id, 'user_id' => $owner->id, 'action' => 'create', 'entity' => 'child', 'entity_id' => 1, 'details' => 'Our action']);
        AuditLog::create(['tenant_id' => $other->id, 'action' => 'create', 'entity' => 'child', 'entity_id' => 2, 'details' => 'Private action']);
        $this->actingAs($teacher)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('audit.index'))->assertInertia(fn (Assert $p) => $p->has('rows.data', 1)->where('rows.data.0.details', 'Our action')->where('rows.total', 1));
    }
}
