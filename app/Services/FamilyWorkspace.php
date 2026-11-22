<?php

namespace App\Services;

use App\Models\Child;
use App\Models\FamilyAccount;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyWorkspace
{
    // Every read requires both an explicit staff grant and the still-current
    // guardian-child link. Never infer access from a name, phone or email match.
    public function grants(FamilyAccount $p)
    {
        return DB::table('family_access as a')
            ->join('guardians as g', 'g.id', '=', 'a.guardian_id')
            ->join('child_guardian as cg', fn ($j) => $j->on('cg.guardian_id', '=', 'g.id')->on('cg.child_id', '=', 'a.child_id'))
            ->where('a.tenant_id', $p->tenant_id)->where('g.tenant_id', $p->tenant_id)
            ->where('a.family_account_id', $p->id)->where('a.active', true)
            ->whereRaw('LOWER(g.email) = ?', [$p->email])->select('a.*');
    }

    public function child(FamilyAccount $p, int $id): Child
    {
        return Child::withoutGlobalScope('tenant')->where('tenant_id', $p->tenant_id)
            ->whereIn('id', $this->grants($p)->select('a.child_id'))->findOrFail($id);
    }

    public function overview(FamilyAccount $p): array
    {
        return ['account' => ['name' => $p->name, 'email' => $p->email],
            'nursery' => ['name' => $p->tenant->name, 'code' => $p->tenant->slug, 'timezone' => $p->tenant->timezone, 'currency' => $p->tenant->currency],
            'children' => Child::withoutGlobalScope('tenant')->where('tenant_id', $p->tenant_id)
                ->whereIn('id', $this->grants($p)->select('a.child_id'))->orderBy('first_name')->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->fullName()])->values(),
            'announcements' => $this->grants($p)->exists() ? DB::table('family_announcements')->where('tenant_id', $p->tenant_id)->orderByDesc('id')->limit(30)->get(['id', 'title', 'body', 'created_at']) : collect()];
    }

    public function detail(FamilyAccount $p, int $id, array $range = []): array
    {
        $c = $this->child($p, $id);
        $to = $range['to'] ?? $p->tenant->today();
        $from = $range['from'] ?? now($p->tenant->timezone)->subDays(29)->toDateString();
        if (strtotime($to) < strtotime($from) || (strtotime($to) - strtotime($from)) / 86400 > 92) {
            throw ValidationException::withMessages(['from' => 'Choose a range of up to 93 days.']);
        }
        $base = fn ($table) => DB::table($table)->where('tenant_id', $p->tenant_id)->where('child_id', $id);
        $grant = $this->grants($p)->where('a.child_id', $id)->first();
        $invoices = collect();
        if ($grant->share_billing) {
            $invoices = Invoice::withoutGlobalScope('tenant')->where('tenant_id', $p->tenant_id)->where('child_id', $id)
                ->whereIn('status', ['sent', 'paid'])->with(['payments' => fn ($q) => $q->withoutGlobalScope('tenant')->where('tenant_id', $p->tenant_id)])->orderByDesc('issue_date')->limit(50)->get()->map(fn ($i) => [
                    'id' => $i->id, 'description' => $i->description, 'due_date' => $i->due_date->toDateString(), 'status' => $i->status,
                    'amount' => Money::format($i->amount_minor, $p->tenant->currency), 'outstanding' => Money::format($i->outstandingMinor(), $p->tenant->currency),
                    'payments' => $i->payments->map(fn ($v) => ['id' => $v->id, 'amount' => Money::format($v->amount_minor, $p->tenant->currency), 'paid_at' => $v->paid_at->toDateString()])->values(),
                ]);
        }

        return ['child' => ['id' => $c->id, 'name' => $c->fullName(), 'status' => $c->status], 'range' => compact('from', 'to'),
            'timezone' => $p->tenant->timezone, 'can_view_billing' => (bool) $grant->share_billing, 'can_request_pickup' => (bool) $grant->pickup_person_id,
            'attendance' => $base('attendance')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->orderByDesc('date')->get(['date', 'check_in_at', 'check_out_at'])->map(function ($a) {
                $a->date = substr($a->date, 0, 10);
                $a->check_in_at = $a->check_in_at ? substr($a->check_in_at, 11, 5) : null;
                $a->check_out_at = $a->check_out_at ? substr($a->check_out_at, 11, 5) : null;

                return $a;
            }),
            'care' => $base('daily_logs')->where('family_visible', true)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->orderByDesc('date')->get(['id', 'date', 'meals', 'naps', 'mood', 'activities', 'notes'])->map(function ($a) {
                $a->date = substr($a->date, 0, 10);

                return $a;
            }),
            'incidents' => $base('incidents')->where('family_visible', true)->orderByDesc('occurred_at')->limit(50)->get(['id', 'occurred_at', 'severity', 'description'])->map(function ($i) use ($p) {
                $i->acknowledged = DB::table('family_incident_acknowledgments')->where('incident_id', $i->id)->where('family_account_id', $p->id)->exists();

                return $i;
            }),
            'invoices' => $invoices,
            'tasks' => $base('family_tasks')->orderByDesc('id')->limit(50)->get(['id', 'title', 'due_date', 'completed_at']),
            'absences' => $base('family_absences')->where('family_account_id', $p->id)->whereNull('cancelled_at')->orderByDesc('starts_on')->limit(30)->get(['id', 'starts_on', 'ends_on', 'reason']),
            'messages' => $base('family_messages')->where('family_account_id',$p->id)->orderByDesc('id')->limit(50)->get(['id', 'body', 'staff_id', 'created_at'])->reverse()->values(),
        ];
    }
}
