<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\DailyLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NurseryDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_is_isolated_labelled_complete_and_refuses_a_second_run(): void
    {
        $existing = $this->createTenant('Existing real nursery');
        $existingUser = $this->createUser($existing);
        $path = tempnam(sys_get_temp_dir(), 'nursery-test-');
        chmod($path, 0600);
        file_put_contents($path, 'TestOnlyNotAnActualLogin123!');
        try {
            $this->artisan('nursery:demo', ['--password-file' => $path, '--confirm-synthetic' => true])->assertSuccessful();
            $t = Tenant::where('slug', 'nursery-synthetic-demo')->firstOrFail();
            $u = User::where('email', 'owner@nursery.example.test')->firstOrFail();
            $this->assertTrue((bool) $t->is_demo);
            $this->assertFalse((bool) $t->ai_enabled);
            $this->assertTrue(Hash::check('TestOnlyNotAnActualLogin123!', $u->password));
            $this->assertSame($t->id, $u->tenant_id);
            $this->assertSame('owner', $u->role);
            $this->assertSame(18, Child::withoutGlobalScopes()->where('tenant_id', $t->id)->count());
            $this->assertGreaterThan(100, Attendance::withoutGlobalScopes()->where('tenant_id', $t->id)->count());
            $this->assertGreaterThan(100, DailyLog::withoutGlobalScopes()->where('tenant_id', $t->id)->count());
            $this->assertSame(18, Invoice::withoutGlobalScopes()->where('tenant_id', $t->id)->count());
            $this->assertSame(8, Admission::withoutGlobalScopes()->where('tenant_id', $t->id)->count());
            $this->assertTrue(TenantSubscription::forTenant($t->id)->writable());
            $this->assertSame(0, Child::withoutGlobalScopes()->where('tenant_id', $existing->id)->count());
            $this->assertSame('Existing real nursery', $existing->fresh()->name);
            $this->assertSame($existing->id, $existingUser->fresh()->tenant_id);
            $before = Payment::withoutGlobalScopes()->count();
            $this->artisan('nursery:demo', ['--password-file' => $path, '--confirm-synthetic' => true])->assertFailed();
            $this->assertSame(2, Tenant::count());
            $this->assertSame($before, Payment::withoutGlobalScopes()->count());
            $this->actingAs($u)->get('/dashboard')->assertOk()->assertInertia(fn ($p) => $p->where('auth.nursery.is_demo', 1));
        } finally {
            unlink($path);
        }
    }

    public function test_demo_requires_explicit_confirmation_protected_password_and_synthetic_identity(): void
    {
        $this->artisan('nursery:demo')->assertFailed();
        $this->artisan('nursery:demo', ['--confirm-synthetic' => true, '--password-file' => '/does-not-exist'])->assertFailed();
        $this->artisan('nursery:demo', ['--confirm-synthetic' => true, '--email' => 'someone@gmail.com'])->assertFailed();
        $this->assertSame(0,Tenant::count());
    }
}
