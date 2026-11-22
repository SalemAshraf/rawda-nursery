<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Child;
use App\Models\Invoice;
use App\Models\OauthCode;
use App\Models\OauthToken;
use App\Models\Payment;
use App\Models\TenantSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AiConnectorTest extends TestCase
{
    use RefreshDatabase;

    private const CB = 'https://claude.ai/api/mcp/auth_callback';

    private function client(array $extra = []): string
    {
        return $this->postJson('/oauth/register', $extra + ['client_name' => 'Claude', 'redirect_uris' => [self::CB]])->assertCreated()->json('client_id');
    }

    private function pkce(): array
    {
        $v = str_repeat('a', 50);

        return [$v, rtrim(strtr(base64_encode(hash('sha256', $v, true)), '+/', '-_'), '=')];
    }

    private function consent($u, $client, $challenge, $decision = 'allow', $extra = []): array
    {
        $q = $extra + ['response_type' => 'code', 'client_id' => $client, 'redirect_uri' => self::CB, 'code_challenge' => $challenge, 'code_challenge_method' => 'S256', 'state' => 'keep-state', 'resource' => rtrim(config('app.url'), '/').'/mcp'];
        $page = $this->actingAs($u->fresh())->get('/oauth/authorize?'.http_build_query($q))->assertOk();
        $rid = $page->viewData('page')['props']['rid'];
        $out = $this->post('/oauth/authorize', ['rid' => $rid, 'decision' => $decision]);
        parse_str((string) parse_url($out->headers->get('Location'), PHP_URL_QUERY), $data);

        return $data;
    }

    private function exchange($client, $code, $v, $extra = [])
    {
        Auth::forgetGuards();

        return $this->post('/oauth/token', $extra + ['client_id' => $client, 'code' => $code, 'code_verifier' => $v, 'redirect_uri' => self::CB, 'grant_type' => 'authorization_code']);
    }

    private function connect($u): array
    {
        $u->tenant->forceFill(['ai_enabled' => true])->save();
        [$v,$ch] = $this->pkce();
        $client = $this->client();
        $code = $this->consent($u, $client, $ch)['code'];

        return [$client, $this->exchange($client, $code, $v)->assertOk()->json()];
    }

    private function mcp($token, $method = 'tools/list', $params = [], $host = null)
    {
        Auth::forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token)->postJson($host ? 'http://'.$host.'/mcp' : '/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
    }

    private function tool($token, $name, $args = []): array
    {
        return json_decode($this->mcp($token, 'tools/call', ['name' => $name, 'arguments' => $args])->assertOk()->json('result.content.0.text'), true);
    }

    public function test_discovery_and_unauthenticated_challenge_do_not_use_an_untrusted_host_for_metadata(): void
    {
        $base = rtrim(config('app.url'), '/');
        $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk()->assertJsonPath('resource', $base.'/mcp');
        $this->getJson('/.well-known/oauth-authorization-server')->assertJsonPath('code_challenge_methods_supported.0', 'S256');
        $this->postJson('/mcp', [])->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Bearer resource_metadata="'.$base.'/.well-known/oauth-protected-resource", scope="nursery:read"');
        $this->getJson('http://forged.example/.well-known/oauth-authorization-server')->assertJsonPath('issuer', $base);
    }

    public function test_registration_rejects_other_redirects_and_secret_clients(): void
    {
        foreach (['https://evil.test/callback', 'https://claude.ai.evil.test/api/mcp/auth_callback', 'javascript:alert(1)'] as $uri) {
            $this->postJson('/oauth/register', ['redirect_uris' => [$uri]])->assertStatus(400);
        }
        $this->postJson('/oauth/register', ['redirect_uris' => [self::CB], 'token_endpoint_auth_method' => 'client_secret_basic'])->assertStatus(400);
        $this->client(['redirect_uris' => ['http://127.0.0.1:4221/callback']]);
    }

    public function test_settings_require_owner_password_consent_and_eligible_subscription(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $teacher = $this->createUser($t, 'teacher');
        $this->actingAs($teacher)->get('/ai')->assertForbidden();
        $this->post('/ai', ['enabled' => true, 'password' => 'password', 'consent' => true])->assertForbidden();
        $this->actingAs($u)->post('/ai', ['enabled' => true, 'password' => 'wrong', 'consent' => true])->assertSessionHasErrors('password');
        $this->post('/ai', ['enabled' => true, 'password' => 'password', 'consent' => false])->assertSessionHasErrors('consent');
        TenantSubscription::create(['tenant_id' => $t->id, 'status' => 'pending']);
        $this->post('/ai', ['enabled' => true, 'password' => 'password', 'consent' => true])->assertForbidden();
        TenantSubscription::query()->update(['status' => 'active']);
        $this->post('/ai', ['enabled' => true, 'password' => 'password', 'consent' => true])->assertRedirect();
        $this->assertTrue((bool) $t->fresh()->ai_enabled);
    }

    public function test_pkce_full_flow_single_use_and_no_raw_tokens_stored(): void
    {
        $u = $this->createUser($this->createTenant());
        $u->tenant->forceFill(['ai_enabled' => true])->save();
        [$v,$ch] = $this->pkce();
        $client = $this->client();
        $out = $this->consent($u, $client, $ch);
        $this->assertSame('keep-state', $out['state']);
        $this->exchange($client, $out['code'], 'bad')->assertStatus(400);
        $tok = $this->exchange($client, $out['code'], $v)->assertOk()->json();
        $this->assertStringStartsWith('nro_', $tok['access_token']);
        $row = OauthToken::first();
        $this->assertSame(hash('sha256', $tok['access_token']), $row->access_hash);
        $this->assertNotContains($tok['access_token'], $row->getAttributes());
        $this->mcp($tok['access_token'])->assertJsonCount(4, 'result.tools');
        $this->exchange($client, $out['code'], $v)->assertStatus(400);
        $this->mcp($tok['access_token'])->assertUnauthorized();
    }

    public function test_deny_and_ai_off_never_issue_a_code(): void
    {
        $u = $this->createUser($this->createTenant());
        [$v,$ch] = $this->pkce();
        $c = $this->client();
        $this->assertSame('access_denied', $this->consent($u, $c, $ch)['error']);
        $u->tenant->forceFill(['ai_enabled' => true])->save();
        $this->assertSame('access_denied', $this->consent($u, $c, $ch, 'deny')['error']);
        $this->assertSame(0, OauthCode::count());
    }

    public function test_unknown_redirect_pkce_scope_resource_and_expired_consent_are_rejected(): void
    {
        $u = $this->createUser($this->createTenant());
        $client = $this->client();
        [$v,$ch] = $this->pkce();
        $q = ['response_type' => 'code', 'client_id' => $client, 'redirect_uri' => self::CB, 'code_challenge' => $ch, 'code_challenge_method' => 'S256'];
        $this->actingAs($u)->get('/oauth/authorize?'.http_build_query(['redirect_uri' => 'https://evil.test/cb'] + $q))->assertStatus(400);
        foreach ([['scope' => 'nursery:write'], ['code_challenge_method' => 'plain'], ['resource' => 'https://evil.test/mcp']] as $bad) {
            $res = $this->get('/oauth/authorize?'.http_build_query($bad + $q));
            $this->assertStringContainsString('error=', $res->headers->get('Location'));
        }
        $page = $this->get('/oauth/authorize?'.http_build_query($q));
        $rid = $page->viewData('page')['props']['rid'];
        $this->travel(11)->minutes();
        $this->post('/oauth/authorize', ['rid' => $rid, 'decision' => 'allow'])->assertStatus(400);
    }

    public function test_refresh_rotation_replay_wrong_client_and_host(): void
    {
        $u = $this->createUser($this->createTenant());
        [$c,$tok] = $this->connect($u);
        $this->post('/oauth/token', ['grant_type' => 'refresh_token', 'client_id' => $this->client(), 'refresh_token' => $tok['refresh_token']])->assertStatus(400);
        $next = $this->post('/oauth/token', ['grant_type' => 'refresh_token', 'client_id' => $c, 'refresh_token' => $tok['refresh_token']])->assertOk()->json();
        $this->mcp($tok['access_token'])->assertUnauthorized();
        $this->mcp($next['access_token'])->assertOk();
        $this->mcp($next['access_token'], 'tools/list', [], 'other.test')->assertUnauthorized();
        $this->post('/oauth/token', ['grant_type' => 'refresh_token', 'client_id' => $c, 'refresh_token' => $tok['refresh_token']])->assertStatus(400);
        $this->mcp($next['access_token'])->assertUnauthorized();
    }

    public function test_role_loss_tenant_move_expiry_and_subscription_stop_access(): void
    {
        $u = $this->createUser($this->createTenant());
        [$c,$tok] = $this->connect($u);
        $u->update(['role' => 'manager']);
        $this->mcp($tok['access_token'])->assertUnauthorized();
        $u->update(['role' => 'owner']);
        $this->mcp($tok['access_token'])->assertOk();
        $other = $this->createTenant();
        $other->forceFill(['ai_enabled' => true])->save();
        $original = $u->tenant_id;
        $u->update(['tenant_id' => $other->id]);
        $this->mcp($tok['access_token'])->assertUnauthorized();
        $this->post('/oauth/token', ['grant_type' => 'refresh_token', 'client_id' => $c, 'refresh_token' => $tok['refresh_token']])->assertStatus(400);
        $u->update(['tenant_id' => $original]);
        [$c,$tok] = $this->connect($u);
        TenantSubscription::create(['tenant_id' => $original, 'status' => 'active', 'period_ends_at' => now()->subMinute()]);
        $this->mcp($tok['access_token'])->assertUnauthorized();
        TenantSubscription::query()->delete();
        OauthToken::query()->update(['access_expires_at' => now()->subMinute()]);
        $this->mcp($tok['access_token'])->assertUnauthorized();
    }

    public function test_revocation_is_tenant_scoped_and_works_when_subscription_expires(): void
    {
        $u = $this->createUser($this->createTenant());
        [$c,$tok] = $this->connect($u);
        $family = OauthToken::first()->family;
        $other = $this->createUser($this->createTenant('Other'));
        $this->actingAs($other)->get('/ai')->assertInertia(fn ($p) => $p->has('connections', 0));
        $this->delete('/ai/connections/'.$family)->assertNotFound();
        $this->mcp($tok['access_token'])->assertOk();
        TenantSubscription::create(['tenant_id' => $u->tenant_id, 'status' => 'suspended']);
        $this->actingAs($u->fresh())->delete('/ai/connections/'.$family)->assertRedirect();
        $this->assertNotNull(OauthToken::first()->revoked_at);
        $this->post('/oauth/revoke', ['token' => 'not-a-token'])->assertOk();
    }

    public function test_disabling_revokes_tokens_and_pending_codes_even_after_reenable(): void
    {
        $u = $this->createUser($this->createTenant());
        [$c,$tok] = $this->connect($u);
        [$v,$ch] = $this->pkce();
        $out = $this->consent($u, $c, $ch);
        $this->actingAs($u->fresh())->post('/ai', ['enabled' => false, 'password' => 'password'])->assertRedirect();
        $this->post('/ai', ['enabled' => true, 'password' => 'password', 'consent' => true])->assertRedirect();
        $this->mcp($tok['access_token'])->assertUnauthorized();
        $this->exchange($c, $out['code'], $v)->assertStatus(400);
    }

    public function test_aggregate_tools_isolate_tenants_and_exclude_every_sensitive_child_field(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $other = $this->createTenant('Other');
        Child::create(['tenant_id' => $t->id, 'first_name' => 'SECRET_CHILD', 'last_name' => 'SECRET_FAMILY', 'health_notes' => 'SECRET_HEALTH', 'allergies' => 'SECRET_ALLERGY', 'status' => 'active']);
        Child::create(['tenant_id' => $other->id, 'first_name' => 'OTHER_SECRET', 'last_name' => 'FAMILY', 'status' => 'active']);
        Admission::create(['tenant_id' => $t->id, 'first_name' => 'SECRET_ADMISSION', 'last_name' => 'FAMILY', 'guardian_name' => 'SECRET_GUARDIAN', 'guardian_phone' => 'PRIVATE_PHONE', 'guardian_relation' => 'mother', 'status' => 'inquiry', 'notes' => 'SECRET_FREE_TEXT']);
        [$c,$tok] = $this->connect($u);
        $a = $this->tool($tok['access_token'], 'attendance_summary');
        $this->assertSame(1, $a['active_enrollment_now']);
        $b = $this->tool($tok['access_token'], 'admissions_summary');
        $this->assertSame(1, (int) $b['counts']['inquiry']);
        $text = json_encode([$a, $b, $this->tool($tok['access_token'], 'classroom_occupancy')]);
        foreach (['SECRET', 'PRIVATE_PHONE', 'health_notes', 'allergies', 'guardian_name', 'child_id', 'first_name'] as $s) {
            $this->assertStringNotContainsString($s, $text);
        }
        // A browser session belonging to the other nursery must not change token tenancy.
        $this->actingAs($this->createUser($other))->withHeader('Authorization', 'Bearer '.$tok['access_token'])->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'attendance_summary']])->assertJsonPath('result.content.0.type', 'text');
    }

    public function test_fees_respect_range_current_balance_and_tenant_even_for_malformed_payment_link(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $other = $this->createTenant();
        $child = Child::create(['tenant_id' => $t->id, 'first_name' => 'Private', 'last_name' => 'Child', 'status' => 'active']);
        $i = Invoice::create(['tenant_id' => $t->id, 'child_id' => $child->id, 'issue_date' => '2026-11-01', 'due_date' => '2026-11-22', 'amount_minor' => 10000, 'status' => 'sent', 'created_by' => $u->id]);
        Payment::create(['tenant_id' => $t->id, 'invoice_id' => $i->id, 'paid_at' => '2026-11-02', 'amount_minor' => 2500, 'method' => 'cash', 'recorded_by' => $u->id]);
        Payment::create(['tenant_id' => $other->id, 'invoice_id' => $i->id, 'paid_at' => '2026-11-02', 'amount_minor' => 99999, 'method' => 'cash', 'recorded_by' => $u->id]);
        [$c,$tok] = $this->connect($u);
        $data = $this->tool($tok['access_token'], 'fees_summary', ['from' => '2026-11-01', 'to' => '2026-11-22']);
        $this->assertSame('25.00', $data['collected']);
        $this->assertSame('100.00', $data['issued']);
        $this->assertSame('75.00', $data['outstanding_now']);
        $this->mcp($tok['access_token'], 'tools/call', ['name' => 'fees_summary', 'arguments' => ['from' => '2020-01-01', 'to' => '2026-01-01']])->assertJsonPath('error.code', -32602);
    }

    public function test_read_only_protocol_rejects_writes_foreign_arguments_and_handles_notifications(): void
    {
        $u = $this->createUser($this->createTenant());
        [$c,$tok] = $this->connect($u);
        $this->mcp($tok['access_token'], 'tools/call', ['name' => 'delete_child'])->assertJsonPath('error.code', -32602);
        $this->mcp($tok['access_token'], 'tools/call', ['name' => 'attendance_summary', 'arguments' => ['tenant_id' => 999]])->assertJsonPath('error.code', -32602);
        $this->mcp($tok['access_token'], 'tools/call', ['name' => 'attendance_summary', 'arguments' => ['date' => 'not-a-date']])->assertJsonPath('error.code', -32602);
        $this->mcp($tok['access_token'], 'resources/write')->assertJsonPath('error.code', -32601);
        $this->withHeader('Authorization', 'Bearer '.$tok['access_token'])->postJson('/mcp', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])->assertStatus(202)->assertContent('');
        $this->mcp($tok['access_token'], 'initialize', ['protocolVersion' => '2025-06-18'])->assertJsonPath('result.serverInfo.name', 'nursery');
        $this->assertSame(0, Child::withoutGlobalScopes()->count());
    }

    public function test_code_exchange_binds_owner_tenant_host_resource_and_expiry(): void
    {
        $u = $this->createUser($this->createTenant());
        $u->tenant->forceFill(['ai_enabled' => true])->save();
        [$v,$ch] = $this->pkce();
        $client = $this->client();
        $out = $this->consent($u, $client, $ch);
        $this->post('http://other.test/oauth/token', ['grant_type' => 'authorization_code', 'client_id' => $client, 'code' => $out['code'], 'redirect_uri' => self::CB, 'code_verifier' => $v])->assertStatus(400);
        $this->exchange($client, $out['code'], $v, ['resource' => 'https://other.test/mcp'])->assertStatus(400);
        $oldTenant = $u->tenant_id;
        $other = $this->createTenant();
        $other->forceFill(['ai_enabled' => true])->save();
        $u->update(['tenant_id' => $other->id]);
        $this->exchange($client, $out['code'], $v)->assertStatus(400);
        $u->update(['tenant_id' => $oldTenant]);
        $out = $this->consent($u, $client, $ch);
        $this->travel(11)->minutes();
        $this->exchange($client, $out['code'], $v)->assertStatus(400);
    }
}
