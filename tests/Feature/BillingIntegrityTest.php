<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(): Invoice
    {
        $t = $this->createTenant();
        $u = $this->createUser($t, 'manager');
        $this->actingAs($u);
        $c = Child::create(['first_name' => 'Test', 'last_name' => 'Child', 'status' => 'active']);

        return Invoice::create(['child_id' => $c->id, 'issue_date' => $t->today(), 'due_date' => $t->today(), 'amount_minor' => 10000, 'status' => 'draft', 'created_by' => $u->id]);
    }

    public function test_invalid_payment_leaves_draft_and_ledger_unchanged(): void
    {
        $i = $this->invoice();
        $this->post(route('billing.payments.store', $i), ['amount' => 'invalid', 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->assertSame('draft', $i->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_cannot_exceed_remaining_balance(): void
    {
        $i = $this->invoice();
        $this->post(route('billing.payments.store', $i), ['amount' => '101', 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->assertSame('draft', $i->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
        $this->post(route('billing.payments.store', $i), ['amount' => '60', 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->post(route('billing.payments.store', $i), ['amount' => '41', 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->assertSame(4000, $i->fresh()->outstandingMinor());
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_final_balance_closes_invoice_and_blocks_additional_recording(): void
    {
        $i = $this->invoice();
        $this->post(route('billing.payments.store', $i), ['amount' => '100', 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $i->fresh()->status);
        $this->post(route('billing.payments.store', $i), ['amount' => '1', 'method' => 'cash'])->assertStatus(422);
        $this->assertDatabaseCount('payments',1);
    }
}
