<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Invoice;
use App\Models\TuitionPlan;
use App\Support\Audit;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        // Billing is nursery administration: owner and manager only.
        return [new Middleware('can:manage-nursery')];
    }

    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $today = $tenant->today();
        $currency = $tenant->currency;

        $invoices = Invoice::with('child', 'payments')->orderByDesc('issue_date')->limit(100)->get();

        return Inertia::render('Billing/Index', [
            'currency' => $currency,
            'plans' => TuitionPlan::orderBy('name')->get()->map(fn (TuitionPlan $p) => [
                'id' => $p->id, 'name' => $p->name, 'period' => $p->period,
                'amount' => Money::format($p->amount_minor, $currency), 'active' => $p->active,
            ]),
            'invoices' => $invoices->map(fn (Invoice $i) => [
                'id' => $i->id,
                'child' => $i->child->fullName(),
                'description' => $i->description,
                'issue_date' => $i->issue_date->toDateString(),
                'due_date' => $i->due_date->toDateString(),
                'amount' => Money::format($i->amount_minor, $currency),
                'paid' => Money::format($i->paidMinor(), $currency),
                'outstanding' => Money::format($i->outstandingMinor(), $currency),
                'status' => $i->status,
                'overdue' => $i->isOverdue($today),
                'payments' => $i->payments->map(fn ($p) => [
                    'id' => $p->id, 'paid_at' => $p->paid_at->toDateString(),
                    'amount' => Money::format($p->amount_minor, $currency),
                    'method' => $p->method, 'reference' => $p->reference,
                ])->values(),
            ]),
            'children' => Child::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->fullName()]),
        ]);
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'amount' => 'required|string|max:20',
            'period' => 'required|in:'.implode(',', TuitionPlan::PERIODS),
        ]);
        $minor = Money::toMinor($data['amount']);
        throw_if($minor === null, ValidationException::withMessages(['amount' => 'Enter a valid amount.']));

        $plan = TuitionPlan::create(['name' => $data['name'], 'amount_minor' => $minor, 'period' => $data['period']]);
        Audit::record('create', 'tuition_plan', $plan->id, $plan->name);

        return redirect()->route('billing.index');
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'child_id' => 'required|integer',
            'description' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'amount' => 'required|string|max:20',
        ]);

        // Scoped find: a child id from another tenant is a 404, not a leak.
        $child = Child::where('status', 'active')->findOrFail($data['child_id']);
        $minor = Money::toMinor($data['amount']);
        throw_if($minor === null || $minor === 0, ValidationException::withMessages(['amount' => 'Enter a valid amount.']));

        $invoice = Invoice::create([
            'child_id' => $child->id,
            'description' => $data['description'] ?? '',
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'amount_minor' => $minor,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);
        Audit::record('create', 'invoice', $invoice->id, $child->fullName());

        return redirect()->route('billing.index');
    }

    public function sendInvoice(Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->status === 'draft', 422);
        $invoice->update(['status' => 'sent']);
        Audit::record('send', 'invoice', $invoice->id);

        return redirect()->route('billing.index');
    }

    public function voidInvoice(Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->status === 'paid', 422);
        abort_if($invoice->payments()->exists(), 422);
        $invoice->update(['status' => 'void']);
        Audit::record('void', 'invoice', $invoice->id);

        return redirect()->route('billing.index');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if(in_array($invoice->status, ['void', 'paid']), 422);
        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        $data = $request->validate([
            'amount' => 'required|string|max:20',
            'method' => 'required|string|max:40',
            'reference' => 'nullable|string|max:100',
        ]);
        $minor = Money::toMinor($data['amount']);
        throw_if($minor === null || $minor === 0, ValidationException::withMessages(['amount' => 'Enter a valid amount.']));

        $invoice->payments()->create([
            'paid_at' => now($request->user()->tenant->timezone),
            'amount_minor' => $minor,
            'method' => $data['method'],
            'reference' => $data['reference'] ?? '',
            'recorded_by' => $request->user()->id,
        ]);
        Audit::record('record-payment', 'invoice', $invoice->id, Money::format($minor, $request->user()->tenant->currency));

        if ($invoice->fresh()->outstandingMinor() === 0) {
            $invoice->update(['status' => 'paid']);
        }

        return redirect()->route('billing.index');
    }
}
