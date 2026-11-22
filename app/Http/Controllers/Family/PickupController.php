<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\FamilyAccount;
use App\Models\PickupPerson;
use App\Services\FamilyWorkspace;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PickupController extends Controller
{
    public function page()
    {
        return Inertia::render('Family/Pickup');
    }

    private function token(Request $r): string
    {
        $d = $r->validate(['token' => 'required|string|max:100']);
        $raw = str_replace('nursery-pickup:v1:', '', $d['token']);
        if (! preg_match('/^[A-Za-z0-9]{64}$/D', $raw)) {
            throw ValidationException::withMessages(['token' => 'Invalid or expired pickup code.']);
        }

        return hash('sha256', $raw);
    }

    private function resolve(Request $r, string $hash, bool $lock = false): array
    {
        $q = DB::table('family_pickup_tokens')->where('tenant_id', $r->user()->tenant_id)->where('token_hash', $hash)->whereNull('consumed_at')->where('expires_at', '>', now());
        $token = $q->first();
        if (! $token) {
            throw ValidationException::withMessages(['token' => 'Invalid or expired pickup code.']);
        }
        $pq = FamilyAccount::where('tenant_id', $r->user()->tenant_id)->where('active', true);
        if ($lock) {
            $pq->lockForUpdate();
        }$p = $pq->find($token->family_account_id);
        if (! $p) {
            throw ValidationException::withMessages(['token' => 'Invalid or expired pickup code.']);
        }
        $s = app(FamilyWorkspace::class);
        $c = $s->child($p, $token->child_id);
        $gq = $s->grants($p)->where('a.child_id', $c->id)->where('a.pickup_person_id', $token->pickup_person_id);
        if ($lock) {
            $gq->lockForUpdate();
        }$grant = $gq->first();
        if (! $grant || $c->status !== 'active') {
            throw ValidationException::withMessages(['token' => 'Pickup authorization is no longer active.']);
        }
        $person = PickupPerson::where('child_id', $c->id)->where('active', true)->find($token->pickup_person_id);
        if (! $person) {
            throw ValidationException::withMessages(['token' => 'Pickup authorization is no longer active.']);
        }
        if ($lock) {
            $token = DB::table('family_pickup_tokens')->where('id', $token->id)->whereNull('consumed_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            if (! $token) {
                throw ValidationException::withMessages(['token' => 'Invalid or expired pickup code.']);
            }
        }

        return [$token, $c, $person];
    }

    public function verify(Request $r)
    {
        [$token,$child,$person] = $this->resolve($r, $this->token($r));

        return response()->json(['child' => $child->fullName(), 'pickup_person' => $person->name, 'expires_at' => $token->expires_at, 'identity_check_required' => true]);
    }

    public function checkout(Request $r)
    {
        $hash = $this->token($r);
        $r->validate(['identity_confirmed' => 'accepted']);
        DB::transaction(function () use ($r, $hash) {
            [$token,$c,$person] = $this->resolve($r, $hash, true);
            $lockedChild = Child::whereKey($c->id)->lockForUpdate()->firstOrFail();
            if ($lockedChild->status !== 'active') {
                throw ValidationException::withMessages(['token' => 'Pickup authorization is no longer active.']);
            }
            // Re-read authorization under lock. QR possession alone is never permission.
            $active = PickupPerson::whereKey($person->id)->where('child_id', $c->id)->where('active', true)->lockForUpdate()->first();
            if (! $active) {
                throw ValidationException::withMessages(['token' => 'Pickup authorization is no longer active.']);
            }
            $a = Attendance::where('child_id', $c->id)->whereDate('date', $r->user()->tenant->today())->lockForUpdate()->first();
            if (! $a || ! $a->check_in_at || $a->check_out_at) {
                throw ValidationException::withMessages(['token' => 'The child is not awaiting pickup today.']);
            }
            $a->update(['check_out_at' => now($r->user()->tenant->timezone), 'checked_out_by' => $r->user()->id, 'pickup_person_id' => $person->id]);
            DB::table('family_pickup_tokens')->where('id', $token->id)->update(['consumed_at' => now()]);
            Audit::record('qr-check-out', 'child', $c->id, 'Staff confirmed physical identity; authorized pickup recorded.');
        }, 3);

        return response()->json(['saved' => true]);
    }
}
