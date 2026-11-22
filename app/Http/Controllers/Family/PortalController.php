<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\FamilyAccount;
use App\Models\PickupPerson;
use App\Services\FamilyWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PortalController extends Controller
{
    private function parent(Request $r): FamilyAccount
    {
        return $r->attributes->get('family_account');
    }

    private function done(Request $r)
    {
        return $r->is('api/*') ? response()->json(['saved' => true]) : back();
    }

    public function home(Request $r, FamilyWorkspace $s)
    {
        $data = $s->overview($this->parent($r));

        return $r->is('api/*') ? response()->json($data) : Inertia::render('Family/Home', $data);
    }

    public function show(Request $r, int $child, FamilyWorkspace $s)
    {
        $range = $r->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d']);
        $data = $s->detail($this->parent($r), $child, $range);

        return $r->is('api/*') ? response()->json($data) : Inertia::render('Family/Child', $data);
    }

    public function message(Request $r, int $child, FamilyWorkspace $s)
    {
        $p = $this->parent($r);
        $s->child($p, $child);
        $d = $r->validate(['body' => 'required|string|max:4000']);
        DB::table('family_messages')->insert(['tenant_id' => $p->tenant_id, 'family_account_id' => $p->id, 'child_id' => $child, 'body' => $d['body'], 'created_at' => now(), 'updated_at' => now()]);

        return $this->done($r);
    }

    public function acknowledge(Request $r, int $child, int $incident, FamilyWorkspace $s)
    {
        $p = $this->parent($r);
        $s->child($p, $child);
        $r->validate(['confirmed' => 'accepted']);
        $i = DB::table('incidents')->where('tenant_id', $p->tenant_id)->where('child_id', $child)->where('family_visible', true)->find($incident);
        abort_unless($i, 404);
        DB::table('family_incident_acknowledgments')->insertOrIgnore(['tenant_id' => $p->tenant_id, 'family_account_id' => $p->id, 'incident_id' => $incident, 'acknowledged_at' => now()]);

        return $this->done($r);
    }

    public function complete(Request $r, int $child, int $task, FamilyWorkspace $s)
    {
        $p = $this->parent($r);
        $s->child($p, $child);
        $q = DB::table('family_tasks')->where('tenant_id', $p->tenant_id)->where('child_id', $child)->where('id', $task);
        abort_unless((clone $q)->exists(), 404);
        $q->whereNull('completed_at')->update(['completed_at' => now(), 'completed_by' => $p->id, 'updated_at' => now()]);

        return $this->done($r);
    }

    public function absence(Request $r, int $child, FamilyWorkspace $s)
    {
        $p = $this->parent($r);
        $s->child($p, $child);
        $d = $r->validate(['starts_on' => 'required|date_format:Y-m-d|after_or_equal:'.$p->tenant->today(), 'ends_on' => 'required|date_format:Y-m-d|after_or_equal:starts_on|before_or_equal:'.now($p->tenant->timezone)->addYear()->toDateString(), 'reason' => 'nullable|string|max:500']);
        DB::table('family_absences')->insert($d + ['tenant_id' => $p->tenant_id, 'family_account_id' => $p->id, 'child_id' => $child, 'created_at' => now(), 'updated_at' => now()]);

        return $this->done($r);
    }

    public function cancelAbsence(Request $r, int $child, int $absence, FamilyWorkspace $s)
    {
        $p = $this->parent($r);
        $s->child($p, $child);
        $r->validate(['confirmed' => 'accepted']);
        DB::transaction(function () use ($p, $child, $absence) {
            $row = DB::table('family_absences')->where('tenant_id', $p->tenant_id)->where('family_account_id', $p->id)->where('child_id', $child)->where('id', $absence)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ($row->cancelled_at) {
                return;
            }
            if ($row->ends_on < $p->tenant->today()) {
                throw ValidationException::withMessages(['absence' => 'A completed absence needs nursery review, not retroactive cancellation.']);
            }
            DB::table('family_absences')->where('id', $row->id)->update(['cancelled_at' => now(), 'updated_at' => now()]);
        }, 3);

        return $this->done($r);
    }

    public function pickup(Request $r, int $child, FamilyWorkspace $s)
    {
        $p = $this->parent($r);
        $payload = DB::transaction(function () use ($p, $child, $s) {
            FamilyAccount::whereKey($p->id)->where('active', true)->lockForUpdate()->firstOrFail();
            $c = $s->child($p, $child);
            $grant = $s->grants($p)->where('a.child_id', $child)->lockForUpdate()->first();
            abort_unless($c->status === 'active' && $grant && $grant->pickup_person_id && PickupPerson::withoutGlobalScope('tenant')->where('tenant_id', $p->tenant_id)->where('child_id', $child)->whereKey($grant->pickup_person_id)->where('active', true)->exists(), 403);
            $token = Str::random(64);
            $expires = now()->addMinutes(2);
            DB::table('family_pickup_tokens')->where('family_account_id', $p->id)->where('child_id', $child)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            DB::table('family_pickup_tokens')->insert(['tenant_id' => $p->tenant_id, 'family_account_id' => $p->id, 'child_id' => $child, 'pickup_person_id' => $grant->pickup_person_id, 'token_hash' => hash('sha256', $token), 'expires_at' => $expires, 'created_at' => now(), 'updated_at' => now()]);

            return ['token' => $token, 'expires_at' => $expires->toIso8601String(), 'identity_check_required' => true];
        }, 3);

        return response()->json($payload);
    }
}
