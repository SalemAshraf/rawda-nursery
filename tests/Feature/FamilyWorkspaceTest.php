<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\DailyLog;
use App\Models\FamilyAccount;
use App\Models\Guardian;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\PickupPerson;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FamilyWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $this->actingAs($u);
        $c = Child::create(['first_name' => 'Sample', 'last_name' => 'Child', 'status' => 'active', 'health_notes' => 'INTERNAL HEALTH']);
        $g = Guardian::create(['name' => 'Sample Guardian', 'email' => 'family@example.test', 'phone' => 'DEMO', 'relation' => 'parent']);
        $c->guardians()->attach($g);
        $p = FamilyAccount::create(['tenant_id' => $t->id, 'name' => $g->name, 'email' => $g->email, 'password' => 'Synthetic-family-fixture-2026']);
        DB::table('family_access')->insert(['tenant_id' => $t->id, 'family_account_id' => $p->id, 'child_id' => $c->id, 'guardian_id' => $g->id, 'approved_by' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
        $token = $p->createToken('test', ['family'], now()->addDay())->plainTextToken;

        return compact('t', 'u', 'c', 'g', 'p', 'token');
    }

    public function test_real_bearer_login_and_child_scope_never_expose_health_notes(): void
    {
        $f = $this->fixture();
        extract($f);
        $this->postJson('/api/v1/family/login', ['nursery_code' => $t->slug, 'email' => $p->email, 'password' => 'Synthetic-family-fixture-2026'])->assertOk()->assertJsonStructure(['token', 'expires_in']);
        $this->withToken($token)->getJson('/api/v1/family/me')->assertOk()->assertJsonPath('children.0.id', $c->id);
        $this->withToken($token)->getJson('/api/v1/family/children/'.$c->id)->assertOk()->assertDontSee('INTERNAL HEALTH')->assertJsonPath('can_view_billing', false);
        $other = $this->createTenant('Other');
        $this->actingAs($this->createUser($other));
        $stranger = Child::create(['first_name' => 'PRIVATE', 'last_name' => 'Other']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/family/children/'.$stranger->id)->assertNotFound();
    }

    public function test_only_published_records_and_explicit_issued_billing_are_shared(): void
    {
        extract($this->fixture());
        DailyLog::create(['child_id' => $c->id, 'date' => $t->today(), 'meals' => 'INTERNAL MEAL', 'created_by' => $u->id]);
        $i = Invoice::create(['child_id' => $c->id, 'issue_date' => $t->today(), 'due_date' => $t->today(), 'amount_minor' => 5000, 'status' => 'draft', 'created_by' => $u->id]);
        $url = '/api/v1/family/children/'.$c->id;
        $this->withToken($token)->getJson($url)->assertOk()->assertJsonCount(0, 'care')->assertJsonCount(0, 'invoices');
        DB::table('daily_logs')->update(['family_visible' => true]);
        DB::table('family_access')->update(['share_billing' => true]);
        $this->getJson($url)->assertJsonPath('care.0.meals', 'INTERNAL MEAL')->assertJsonCount(0, 'invoices');
        $i->update(['status' => 'sent']);
        $this->getJson($url)->assertJsonCount(1, 'invoices');
    }

    public function test_revoked_access_changed_guardian_link_and_disabled_account_stop_tokens(): void
    {
        extract($this->fixture());
        $url = '/api/v1/family/children/'.$c->id;
        DB::table('family_access')->update(['active' => false]);
        $this->withToken($token)->getJson($url)->assertNotFound();
        DB::table('family_access')->update(['active' => true]);
        $g->update(['email' => 'different@example.test']);
        $this->getJson($url)->assertNotFound();
        $g->update(['email' => $p->email]);
        $p->update(['active' => false]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson($url)->assertUnauthorized();
    }

    public function test_parent_cannot_enter_staff_or_platform_and_staff_cannot_use_family_api(): void
    {
        extract($this->fixture());
        $this->withToken($token)->getJson('/api/v1/family/me')->assertOk();
        $this->post('/family/login', ['nursery_code' => $t->slug, 'email' => $p->email, 'password' => 'Synthetic-family-fixture-2026'])->assertRedirect('/family');
        $this->app['auth']->guard('web')->logout();
        $this->get('/families')->assertRedirect('/login');
        $this->get('/platform')->assertRedirect('/platform/login');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer invalid')->getJson('/api/v1/family/me')->assertUnauthorized();
    }

    public function test_invitation_requires_explicit_identity_approval_and_is_single_use(): void
    {
        extract($this->fixture());
        $this->actingAs($u);
        $d = ['child_id' => $c->id, 'guardian_id' => $g->id, 'email' => 'new@example.test', 'share_billing' => true];
        $this->post('/families/invite', $d)->assertSessionHasErrors('identity_confirmed');
        $res = $this->post('/families/invite', $d + ['identity_confirmed' => true])->assertRedirect();
        $url = session('family_invite');
        parse_str(parse_url($url, PHP_URL_FRAGMENT), $parts);
        $raw = $parts['token'];
        $this->assertDatabaseMissing('family_invites', ['token_hash' => $raw]);
        $this->assertDatabaseHas('family_invites', ['token_hash' => hash('sha256', $raw)]);
        $act = ['token' => $raw, 'email' => 'new@example.test', 'password' => 'Synthetic-new-family-2026', 'password_confirmation' => 'Synthetic-new-family-2026'];
        $this->postJson('/api/v1/family/activate', $act)->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/v1/family/activate', $act)->assertUnprocessable();
        $this->assertDatabaseHas('family_access', ['child_id' => $c->id, 'share_billing' => true, 'guardian_id' => $g->id]);
    }

    public function test_invitation_cannot_reset_an_existing_account_password(): void
    {
        extract($this->fixture());
        $raw = str_repeat('x', 64);
        DB::table('family_invites')->insert(['tenant_id' => $t->id, 'child_id' => $c->id, 'guardian_id' => $g->id, 'email' => $p->email, 'issued_by' => $u->id, 'token_hash' => hash('sha256', $raw), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson('/api/v1/family/activate', ['token' => $raw, 'email' => $p->email, 'password' => 'Different-password-2026', 'password_confirmation' => 'Different-password-2026'])->assertUnprocessable();
        $this->assertTrue(Hash::check('Synthetic-family-fixture-2026', $p->fresh()->password));
    }

    public function test_messages_are_per_account_and_incident_ack_is_explicit_and_idempotent(): void
    {
        extract($this->fixture());
        $base = '/api/v1/family/children/'.$c->id;
        $this->withToken($token)->postJson($base.'/messages', ['body' => 'A family message'])->assertOk();
        $this->assertDatabaseHas('family_messages', ['family_account_id' => $p->id, 'staff_id' => null]);
        $i = Incident::create(['child_id' => $c->id, 'occurred_at' => now(), 'severity' => 'minor', 'description' => 'Shared sample', 'reported_by' => $u->id]);
        $this->postJson($base.'/incidents/'.$i->id.'/acknowledge', ['confirmed' => true])->assertNotFound();
        $i->update(['family_visible' => true]);
        $this->postJson($base.'/incidents/'.$i->id.'/acknowledge', [])->assertUnprocessable();
        $this->postJson($base.'/incidents/'.$i->id.'/acknowledge', ['confirmed' => true])->assertOk();
        $this->postJson($base.'/incidents/'.$i->id.'/acknowledge', ['confirmed' => true])->assertOk();
        $this->assertDatabaseCount('family_incident_acknowledgments', 1);
        $this->assertNull($i->fresh()->guardian_acknowledged_at);
    }

    public function test_pickup_code_never_creates_authorization_and_expires_after_two_minutes(): void
    {
        extract($this->fixture());
        $url = '/api/v1/family/children/'.$c->id.'/pickup-token';
        $this->withToken($token)->postJson($url)->assertForbidden();
        $person = PickupPerson::create(['child_id' => $c->id, 'name' => 'Approved sample', 'relation' => 'parent', 'active' => true]);
        DB::table('family_access')->update(['pickup_person_id' => $person->id]);
        $a = $this->postJson($url)->assertOk()->assertJsonPath('identity_check_required', true)->json();
        $this->assertDatabaseHas('family_pickup_tokens', ['token_hash' => hash('sha256', $a['token'])]);
        $this->assertEqualsWithDelta(120, now()->diffInSeconds(Carbon::parse($a['expires_at'])), 2);
        $person->update(['active' => false]);
        $this->postJson($url)->assertForbidden();
    }

    public function test_teacher_cannot_invite_or_publish_and_editing_a_care_record_unpublishes_it(): void
    {
        extract($this->fixture());
        $teacher = $this->createUser($t, 'teacher');
        $this->actingAs($teacher);
        $this->get('/families')->assertForbidden();
        $this->post('/families/invite', [])->assertForbidden();
        $log = DailyLog::create(['child_id' => $c->id, 'date' => $t->today(), 'meals' => 'Lunch', 'family_visible' => true, 'created_by' => $u->id]);
        $this->post('/logs/'.$c->id, ['date' => $t->today(), 'notes' => 'Internal edit'])->assertRedirect();
        $this->assertFalse((bool) $log->fresh()->family_visible);
    }

    public function test_pickup_requires_physical_identity_and_is_single_use(): void
    {
        extract($this->fixture());
        $person = PickupPerson::create(['child_id' => $c->id, 'name' => 'Authorized sample', 'relation' => 'parent', 'active' => true]);
        DB::table('family_access')->update(['pickup_person_id' => $person->id]);
        $payload = $this->withToken($token)->postJson('/api/v1/family/children/'.$c->id.'/pickup-token')->assertOk()->json();
        $this->app['auth']->forgetGuards();
        $this->actingAs($u, 'web');
        $this->withoutHeader('Authorization');
        $data = ['token' => 'nursery-pickup:v1:'.$payload['token']];
        $this->postJson('/pickup-scan/verify', $data)->assertOk()->assertJsonPath('pickup_person', $person->name);
        $this->postJson('/pickup-scan/checkout', $data)->assertUnprocessable();
        $this->postJson('/pickup-scan/checkout', $data + ['identity_confirmed' => true])->assertUnprocessable();
        Attendance::create(['child_id' => $c->id, 'date' => $t->today(), 'check_in_at' => now(), 'checked_in_by' => $u->id]);
        $this->postJson('/pickup-scan/checkout', $data + ['identity_confirmed' => true])->assertOk()->assertJsonPath('saved', true);
        $this->assertDatabaseHas('attendance', ['child_id' => $c->id, 'pickup_person_id' => $person->id, 'checked_out_by' => $u->id]);
        $this->postJson('/pickup-scan/checkout', $data + ['identity_confirmed' => true])->assertUnprocessable();
        $this->postJson('/pickup-scan/verify', $data)->assertUnprocessable();
    }

    public function test_pickup_rechecks_revocation_expiry_and_tenant(): void
    {
        extract($this->fixture());
        $raw = str_repeat('a', 64);
        $person = PickupPerson::create(['child_id' => $c->id, 'name' => 'Authorized sample', 'relation' => 'parent', 'active' => true]);
        DB::table('family_access')->update(['pickup_person_id' => $person->id]);
        DB::table('family_pickup_tokens')->insert(['tenant_id' => $t->id, 'child_id' => $c->id, 'family_account_id' => $p->id, 'pickup_person_id' => $person->id, 'token_hash' => hash('sha256', $raw), 'expires_at' => now()->addMinutes(2), 'created_at' => now(), 'updated_at' => now()]);
        $data = ['token' => $raw, 'identity_confirmed' => true];
        $this->postJson('/pickup-scan/verify', $data)->assertOk();
        DB::table('family_access')->update(['active' => false]);
        $this->postJson('/pickup-scan/checkout', $data)->assertNotFound();
        DB::table('family_access')->update(['active' => true]);
        $person->update(['active' => false]);
        $this->postJson('/pickup-scan/checkout', $data)->assertUnprocessable();
        $person->update(['active' => true]);
        $this->travel(121)->seconds();
        $this->postJson('/pickup-scan/verify', $data)->assertUnprocessable();
        $this->travelBack();
        $other = $this->createTenant('Other pickup tenant');
        $this->actingAs($this->createUser($other), 'web');
        $this->postJson('/pickup-scan/verify', $data)->assertUnprocessable();
        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_revoked_last_grant_hides_new_announcements_and_other_accounts_messages(): void
    {
        extract($this->fixture());
        DB::table('family_announcements')->insert(['tenant_id' => $t->id, 'created_by' => $u->id, 'title' => 'Current families only', 'body' => 'Notice', 'created_at' => now(), 'updated_at' => now()]);
        $other = FamilyAccount::create(['tenant_id' => $t->id, 'name' => 'Second family', 'email' => 'second@example.test', 'password' => 'Synthetic-second-fixture-2026']);
        DB::table('family_messages')->insert(['tenant_id' => $t->id, 'family_account_id' => $other->id, 'child_id' => $c->id, 'body' => 'OTHER ACCOUNT PRIVATE', 'created_at' => now(), 'updated_at' => now()]);
        $this->withToken($token)->getJson('/api/v1/family/children/'.$c->id)->assertOk()->assertDontSee('OTHER ACCOUNT PRIVATE');
        $this->getJson('/api/v1/family/me')->assertJsonCount(1, 'announcements');
        DB::table('family_access')->update(['active' => false]);
        $this->getJson('/api/v1/family/me')->assertJsonCount(0, 'announcements')->assertJsonCount(0, 'children');
    }

    public function test_reads_do_not_consume_the_pickup_rate_limit(): void
    {
        extract($this->fixture());
        $person = PickupPerson::create(['child_id' => $c->id, 'name' => 'Approved sample', 'relation' => 'parent', 'active' => true]);
        DB::table('family_access')->update(['pickup_person_id' => $person->id]);
        for ($i = 0; $i < 15; $i++) {
            $this->withToken($token)->getJson('/api/v1/family/me')->assertOk();
        }
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/family/children/'.$c->id.'/pickup-token')->assertOk();
        }
        $this->postJson('/api/v1/family/children/'.$c->id.'/pickup-token')->assertStatus(429);
        $this->assertDatabaseCount('family_pickup_tokens', 6);
        $this->assertEquals(1, DB::table('family_pickup_tokens')->whereNull('consumed_at')->count());
    }

    public function test_planned_absence_does_not_change_attendance_or_fees(): void
    {
        extract($this->fixture());
        $base = '/api/v1/family/children/'.$c->id;
        $this->withToken($token)->postJson($base.'/absences', ['starts_on' => $t->today(), 'ends_on' => $t->today(), 'reason' => 'Family plan'])->assertOk();
        $this->assertDatabaseCount('family_absences',1);
        $this->assertDatabaseCount('attendance',0);
        $this->assertDatabaseCount('invoices',0);
        $this->getJson($base.'?from=2020-01-01&to=2026-11-22')->assertUnprocessable();
    }
}
