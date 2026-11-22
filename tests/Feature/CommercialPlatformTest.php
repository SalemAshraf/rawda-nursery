<?php

namespace Tests\Feature;

use App\Models\PlatformAdmin;
use App\Models\SaasPlan;
use App\Models\SalesInquiry;
use App\Models\TenantSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommercialPlatformTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return PlatformAdmin::create(['name' => 'Test platform', 'email' => 'platform@example.test', 'password' => Hash::make('synthetic-test-password')]);
    }

    public function test_public_catalog_excludes_drafts_and_contains_no_assumed_price(): void
    {
        SaasPlan::create(['name_ar' => 'مسودة', 'name_en' => 'Draft', 'published' => false]);
        SaasPlan::create(['name_ar' => 'حسب الطلب', 'name_en' => 'On request', 'published' => true]);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Welcome')->has('plans', 1)->where('plans.0.price_minor', null));
    }

    public function test_public_inquiry_requires_consent_and_published_plan(): void
    {
        $d = ['nursery_name' => 'Example Nursery', 'name' => 'Test Person', 'email' => 'person@example.test', 'message' => 'Team needs'];
        $this->post(route('sales.inquiry'), $d)->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('sales_inquiries', 0);
        $draft = SaasPlan::create(['name_ar' => 'مسودة', 'name_en' => 'Draft']);
        $this->post(route('sales.inquiry'), $d + ['consent' => true, 'saas_plan_id' => $draft->id])->assertSessionHasErrors('saas_plan_id');
        $this->post(route('sales.inquiry'), $d + ['consent' => true])->assertRedirect();
        $this->assertDatabaseCount('sales_inquiries', 1);
    }

    public function test_nursery_owner_cannot_become_platform_admin_or_read_platform_data(): void
    {
        $u = $this->createUser($this->createTenant());
        $this->actingAs($u, 'web')->get(route('platform.index'))->assertRedirect(route('platform.login'));
        $this->post(route('platform.login.store'), ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_platform_plan_save_uses_exact_money_and_requires_platform_guard(): void
    {
        $a = $this->admin();
        $d = ['name_ar' => 'باقة اختبار', 'name_en' => 'Test plan', 'price' => '1250.50', 'currency' => 'EGP', 'interval' => 'monthly', 'published' => true, 'sort' => 0];
        $this->actingAs($a, 'platform')->post(route('platform.plans.store'), $d)->assertRedirect();
        $this->assertDatabaseHas('saas_plans', ['price_minor' => 125050, 'published' => 1]);
        $d['price'] = '1.009';
        $this->post(route('platform.plans.store'), $d)->assertSessionHasErrors('price');
        $this->assertDatabaseCount('saas_plans', 1);
        $this->assertDatabaseHas('platform_audits', ['action' => 'plan.saved']);
    }

    public function test_legacy_access_stays_working_and_pending_subscriptions_are_read_only(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $this->actingAs($u, 'web')->post(route('classrooms.store'), ['name' => 'Legacy room'])->assertRedirect();
        TenantSubscription::create(['tenant_id' => $t->id, 'status' => 'pending']);
        $this->post(route('classrooms.store'), ['name' => 'Blocked room'])->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('classrooms', ['name' => 'Blocked room']);
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('subscription.index'))->assertOk();
        $this->post(route('subscription.request'), ['message' => 'Please review'])->assertRedirect();
        $this->assertDatabaseHas('sales_inquiries', ['tenant_id' => $t->id]);
    }

    public function test_owner_subscription_and_requests_do_not_cross_tenants_or_reach_teacher(): void
    {
        $t = $this->createTenant();
        $other = $this->createTenant('Other');
        $u = $this->createUser($t);
        SalesInquiry::create(['tenant_id' => $other->id, 'nursery_name' => 'Other', 'name' => 'Other person', 'email' => 'other@example.test', 'message' => 'Private needs']);
        $this->actingAs($u, 'web')->get(route('subscription.index'))->assertInertia(fn (Assert $p) => $p->has('requests', 0)->where('legacy', true));
        $this->actingAs($this->createUser($t, 'teacher'), 'web')->get(route('subscription.index'))->assertForbidden();
    }

    public function test_admin_can_activate_after_explicit_confirmation_without_collecting_money(): void
    {
        $t = $this->createTenant();
        TenantSubscription::create(['tenant_id' => $t->id, 'status' => 'pending']);
        $a = $this->admin();
        $d = ['status' => 'active', 'period_ends_at' => now()->addMonth()->toDateTimeString()];
        $this->actingAs($a, 'platform')->put(route('platform.subscriptions.update', $t), $d)->assertSessionHasErrors('confirm');
        $this->put(route('platform.subscriptions.update', $t), $d + ['confirm' => true])->assertRedirect();
        $this->assertDatabaseHas('tenant_subscriptions', ['tenant_id' => $t->id, 'status' => 'active']);
        $this->assertDatabaseCount('payments', 0);
        $this->get(route('platform.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->has('nurseries.data', 1)->missing('children')->missing('health_notes'));
    }

    public function test_expired_and_suspended_subscriptions_do_not_write_but_active_ones_do(): void
    {
        $t = $this->createTenant();
        $u = $this->createUser($t);
        $s = TenantSubscription::create(['tenant_id' => $t->id, 'status' => 'active', 'period_ends_at' => now()->subMinute()]);
        $this->actingAs($u, 'web')->post(route('classrooms.store'), ['name' => 'Expired'])->assertRedirect(route('dashboard'));
        $s->update(['period_ends_at' => now()->addDay()]);
        $this->post(route('classrooms.store'), ['name' => 'Active'])->assertRedirect();
        $this->assertDatabaseHas('classrooms', ['name' => 'Active']);
        $s->update(['status' => 'suspended']);
        $this->post(route('classrooms.store'), ['name' => 'Suspended'])->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('classrooms',['name' => 'Suspended']);
    }
}
