<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function childOf($tenant): Child
    {
        return Child::withoutTenantScope(fn () => Child::create([
            'tenant_id' => $tenant->id, 'first_name' => 'Yassin', 'last_name' => 'K', 'status' => 'active',
        ]));
    }

    public function test_teacher_cannot_reach_billing(): void
    {
        $tenant = $this->createTenant();
        $teacher = $this->createUser($tenant, 'teacher');

        $this->actingAs($teacher)->get(route('billing.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('billing.plans.store'), [
            'name' => 'X', 'amount' => '100', 'period' => 'monthly',
        ])->assertForbidden();
    }

    public function test_invoice_flow_to_paid_with_partial_payments(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createUser($tenant, 'manager');
        $child = $this->childOf($tenant);

        $this->actingAs($manager)->post(route('billing.invoices.store'), [
            'child_id' => $child->id,
            'description' => 'November tuition',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'amount' => '1500.00',
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::withoutGlobalScopes()->first();
        $this->assertSame(150000, $invoice->amount_minor);
        $this->assertSame('draft', $invoice->status);

        $this->actingAs($manager)->post(route('billing.invoices.send', $invoice));
        $this->assertSame('sent', $invoice->fresh()->status);

        $this->actingAs($manager)->post(route('billing.payments.store', $invoice), [
            'amount' => '1000', 'method' => 'cash',
        ]);
        $this->assertSame('sent', $invoice->fresh()->status);
        $this->assertSame(50000, $invoice->fresh()->outstandingMinor());

        $this->actingAs($manager)->post(route('billing.payments.store', $invoice), [
            'amount' => '500', 'method' => 'bank transfer', 'reference' => 'REF-1',
        ]);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_invoice_with_payments_cannot_be_voided_and_paid_invoice_takes_no_payment(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createUser($tenant, 'manager');
        $child = $this->childOf($tenant);

        $this->actingAs($manager)->post(route('billing.invoices.store'), [
            'child_id' => $child->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'amount' => '100',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->first();

        $this->actingAs($manager)->post(route('billing.payments.store', $invoice), [
            'amount' => '100', 'method' => 'cash',
        ]);

        $this->actingAs($manager)->post(route('billing.invoices.void', $invoice))->assertStatus(422);
        $this->actingAs($manager)->post(route('billing.payments.store', $invoice), [
            'amount' => '10', 'method' => 'cash',
        ])->assertStatus(422);

        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_plan_and_invoice_require_valid_amounts(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createUser($tenant, 'manager');
        $child = $this->childOf($tenant);

        $this->actingAs($manager)->post(route('billing.plans.store'), [
            'name' => 'Bad', 'amount' => 'abc', 'period' => 'monthly',
        ])->assertSessionHasErrors('amount');

        $this->actingAs($manager)->post(route('billing.invoices.store'), [
            'child_id' => $child->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'amount' => '0',
        ])->assertSessionHasErrors('amount');
    }
}
