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

    public function test_staff_reply_stays_with_the_authorized_family_account(): void
    {
        extract($this->fixture());
        $this->actingAs($u, 'web');
        $this->post('/families/reply', ['family_account_id' => $p->id, 'child_id' => $c->id, 'body' => 'Approved staff reply'])->assertRedirect();
        $this->assertDatabaseHas('family_messages', ['family_account_id' => $p->id, 'child_id' => $c->id, 'staff_id' => $u->id, 'body' => 'Approved staff reply']);
        DB::table('family_access')->update(['active' => false]);
        $this->post('/families/reply', ['family_account_id' => $p->id, 'child_id' => $c->id, 'body' => 'Must not be sent'])->assertNotFound();
        $this->assertDatabaseMissing('family_messages', ['body' => 'Must not be sent']);
    }

    public function test_family_can_cancel_own_current_absence_without_erasing_attendance_or_fees(): void
    {
        extract($this->fixture());
        $base = '/api/v1/family/children/'.$c->id;
        $this->withToken($token)->postJson($base.'/absences', ['starts_on' => $t->today(), 'ends_on' => $t->today(), 'reason' => 'Original notice'])->assertOk();
        $id = DB::table('family_absences')->value('id');
        $url = $base.'/absences/'.$id.'/cancel';
        $this->getJson($base)->assertJsonPath('absences.0.can_cancel', true);
        $this->postJson($url, [])->assertUnprocessable();
        $this->postJson($url, ['confirmed' => true])->assertOk();
        $this->postJson($url, ['confirmed' => true])->assertOk();
        $this->assertDatabaseCount('family_absences', 1);
        $this->assertNotNull(DB::table('family_absences')->value('cancelled_at'));
        $this->getJson($base)->assertJsonCount(0, 'absences');
        $this->assertDatabaseCount('attendance', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_completed_absences_and_another_account_cannot_be_cancelled(): void
    {
        extract($this->fixture());
        $old = DB::table('family_absences')->insertGetId(['tenant_id' => $t->id, 'family_account_id' => $p->id, 'child_id' => $c->id, 'starts_on' => now()->subDays(2)->toDateString(), 'ends_on' => now()->subDay()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        $base = '/api/v1/family/children/'.$c->id.'/absences/';
        $this->withToken($token)->postJson($base.$old.'/cancel', ['confirmed' => true])->assertUnprocessable();
        $other = FamilyAccount::create(['tenant_id' => $t->id, 'name' => 'Other guardian', 'email' => 'other@example.test', 'password' => 'Synthetic-other-fixture-2026']);
        $g2 = Guardian::create(['name' => 'Other guardian', 'email' => $other->email, 'phone' => 'SYNTHETIC', 'relation' => 'parent']);
        $c->guardians()->attach($g2);
        DB::table('family_access')->insert(['tenant_id' => $t->id, 'family_account_id' => $other->id, 'child_id' => $c->id, 'guardian_id' => $g2->id, 'approved_by' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
        $otherToken = $other->createToken('test', ['family'], now()->addDay())->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)->postJson($base.$old.'/cancel', ['confirmed' => true])->assertNotFound();
        $this->assertNull(DB::table('family_absences')->where('id', $old)->value('cancelled_at'));
    }

    public function test_stale_publication_does_not_share_a_new_unreviewed_care_text(): void
    {
        extract($this->fixture());
        $this->actingAs($u, 'web');
        $log = DailyLog::create(['child_id' => $c->id, 'date' => $t->today(), 'meals' => 'Reviewed text', 'notes' => 'Original note', 'created_by' => $u->id]);
        $raw = DB::table('daily_logs')->find($log->id);
        $hash = hash('sha256', json_encode(['care', $raw->id, $raw->date, $raw->meals, $raw->naps, $raw->mood, $raw->activities, $raw->notes]));
        $log->update(['notes' => 'A newer internal note']);
        $this->postJson('/families/publish/care/'.$log->id, ['visible' => true, 'review_hash' => $hash])->assertUnprocessable();
        $this->assertFalse((bool) $log->fresh()->family_visible);
    }

    public function test_current_publication_requires_exact_review_proof_and_hiding_does_not(): void
    {
        extract($this->fixture());
        $this->actingAs($u, 'web');
        $log = DailyLog::create(['child_id' => $c->id, 'date' => $t->today(), 'meals' => 'Current reviewed text', 'created_by' => $u->id]);
        $url = '/families/publish/care/'.$log->id;
        $this->postJson($url, ['visible' => true])->assertUnprocessable();
        $row = DB::table('daily_logs')->find($log->id);
        $hash = hash('sha256', json_encode(['care', $row->id, $row->date, $row->meals, $row->naps, $row->mood, $row->activities, $row->notes]));
        $this->postJson($url, ['visible' => true, 'review_hash' => $hash])->assertRedirect();
        $this->assertTrue((bool) $log->fresh()->family_visible);
        $this->postJson($url, ['visible' => false])->assertRedirect();
        $this->assertFalse((bool) $log->fresh()->family_visible);
        $teacher = $this->createUser($t, 'teacher');
        $this->actingAs($teacher, 'web');
        $this->postJson($url, ['visible' => true, 'review_hash' => $hash])->assertForbidden();
    }

    public function test_incident_review_proof_covers_description_time_and_severity(): void
    {
        extract($this->fixture());
        $this->actingAs($u, 'web');
        $i = Incident::create(['child_id' => $c->id, 'occurred_at' => now(), 'severity' => 'minor', 'description' => 'Initial sample', 'reported_by' => $u->id]);
        $row = DB::table('incidents')->find($i->id);
        $old = hash('sha256', json_encode(['incident', $row->id, $row->occurred_at, $row->severity, $row->description]));
        $i->update(['severity' => 'moderate']);
        $url = '/families/publish/incident/'.$i->id;
        $this->postJson($url, ['visible' => true, 'review_hash' => $old])->assertUnprocessable();
        $this->assertFalse((bool) $i->fresh()->family_visible);
        $row = DB::table('incidents')->find($i->id);
        $fresh = hash('sha256', json_encode(['incident', $row->id, $row->occurred_at, $row->severity, $row->description]));
        $this->postJson($url, ['visible' => true, 'review_hash' => $fresh])->assertRedirect();
        $this->assertTrue((bool) $i->fresh()->family_visible);
    }

    public function test_review_filters_find_exact_child_day_without_exposing_another_tenant(): void
    {
        extract($this->fixture());
        $this->actingAs($u, 'web');
        DailyLog::create(['child_id' => $c->id, 'date' => $t->today(), 'notes' => 'Current review text', 'created_by' => $u->id]);
        DailyLog::create(['child_id' => $c->id, 'date' => now($t->timezone)->subDay()->toDateString(), 'notes' => 'Another day text', 'created_by' => $u->id]);
        $this->get('/families?review_child='.$c->id.'&review_date='.$t->today())->assertOk()->assertSee('Current review text')->assertSee('review_hash')->assertDontSee('Another day text');
        $other = $this->createTenant('Other review tenant');
        $this->actingAs($this->createUser($other), 'web');
        $private = Child::create(['first_name' => 'Other', 'last_name' => 'Child', 'status' => 'active']);
        $this->actingAs($u, 'web');
        $this->get('/families?review_child='.$private->id)->assertNotFound();
    }

    public function test_planned_absence_does_not_change_attendance_or_fees(): void
    {
        extract($this->fixture());
        $base = '/api/v1/family/children/'.$c->id;
        $this->withToken($token)->postJson($base.'/absences', ['starts_on' => $t->today(), 'ends_on' => $t->today(), 'reason' => 'Family plan'])->assertOk();
        $this->assertDatabaseCount('family_absences', 1);
        $this->assertDatabaseCount('attendance', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->getJson($base.'?from=2020-01-01&to=2026-11-22')->assertUnprocessable();
    }
}
