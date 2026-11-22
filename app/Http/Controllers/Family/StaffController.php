<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\FamilyAccount;
use App\Services\FamilyWorkspace;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('manage-nursery');
        $t = $r->user()->tenant_id;

        return Inertia::render('Family/Manage', [
            'nurseryCode' => $r->user()->tenant->slug,
            'inviteUrl' => $r->session()->get('family_invite'),
            'children' => Child::with(['guardians', 'activePickupPersons'])->orderBy('first_name')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->fullName(), 'guardians' => $c->guardians->map(fn ($g) => $g->only('id', 'name', 'email')), 'pickups' => $c->activePickupPersons->map(fn ($p) => $p->only('id', 'name'))]),
            'access' => DB::table('family_access as a')->join('family_accounts as p', 'p.id', '=', 'a.family_account_id')->join('children as c', 'c.id', '=', 'a.child_id')->where('a.tenant_id', $t)->orderByDesc('a.id')->get(['a.id', 'a.active', 'a.share_billing', 'p.name', 'p.email', 'c.first_name', 'c.last_name']),
            'messages' => DB::table('family_messages as m')->join('family_accounts as p', 'p.id', '=', 'm.family_account_id')->join('children as c', 'c.id', '=', 'm.child_id')->where('m.tenant_id', $t)->orderByDesc('m.id')->limit(60)->get(['m.id', 'm.child_id', 'm.family_account_id', 'm.staff_id', 'm.body', 'm.created_at', 'p.name', 'c.first_name', 'c.last_name']),
            'announcements' => DB::table('family_announcements')->where('tenant_id', $t)->orderByDesc('id')->limit(15)->get(['id', 'title', 'body']),
            'care' => DB::table('daily_logs as l')->join('children as c', 'c.id', '=', 'l.child_id')->where('l.tenant_id', $t)->orderByDesc('l.date')->limit(30)->get(['l.id', 'l.date', 'l.meals', 'l.naps', 'l.mood', 'l.activities', 'l.notes', 'l.family_visible', 'c.first_name', 'c.last_name']),
            'incidents' => DB::table('incidents as i')->join('children as c', 'c.id', '=', 'i.child_id')->where('i.tenant_id', $t)->orderByDesc('i.occurred_at')->limit(30)->get(['i.id', 'i.description', 'i.occurred_at', 'i.family_visible', 'c.first_name', 'c.last_name']),
            'absences' => DB::table('family_absences as a')->join('children as c', 'c.id', '=', 'a.child_id')->where('a.tenant_id', $t)->whereNull('a.cancelled_at')->orderByDesc('a.starts_on')->limit(40)->get(['a.id', 'a.starts_on', 'a.ends_on', 'a.reason', 'c.first_name', 'c.last_name']),
        ]);
    }

    public function invite(Request $r)
    {
        Gate::authorize('manage-nursery');
        $d = $r->validate(['child_id' => 'required|integer', 'guardian_id' => 'required|integer', 'email' => 'required|email|max:255', 'pickup_person_id' => 'nullable|integer', 'share_billing' => 'boolean', 'identity_confirmed' => 'accepted']);
        $c = Child::findOrFail($d['child_id']);
        $g = $c->guardians()->whereKey($d['guardian_id'])->firstOrFail();
        $email = strtolower(trim($d['email']));
        if (! empty($d['pickup_person_id'])) {
            abort_unless($c->activePickupPersons()->whereKey($d['pickup_person_id'])->exists(), 422);
        }
        $raw = Str::random(64);
        DB::transaction(function () use ($r, $d, $c, $g, $email, $raw) {
            $g->update(['email' => $email]);
            DB::table('family_invites')->where('tenant_id', $r->user()->tenant_id)->where('child_id', $c->id)->where('email', $email)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            DB::table('family_invites')->insert(['tenant_id' => $r->user()->tenant_id, 'child_id' => $c->id, 'guardian_id' => $g->id, 'pickup_person_id' => $d['pickup_person_id'] ?? null, 'share_billing' => $d['share_billing'] ?? false, 'token_hash' => hash('sha256', $raw), 'email' => $email, 'issued_by' => $r->user()->id, 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('invite-family', 'child', $c->id, 'Explicit guardian access approved; no external message sent.');
        });

        return back()->with('family_invite', route('family.activate').'#token='.$raw);
    }

    public function revoke(Request $r, int $access)
    {
        Gate::authorize('manage-nursery');
        $a = DB::table('family_access')->where('tenant_id', $r->user()->tenant_id)->find($access);
        abort_unless($a, 404);
        DB::transaction(function () use ($r, $a) {
            DB::table('family_access')->where('id', $a->id)->update(['active' => false, 'updated_at' => now()]);
            DB::table('family_pickup_tokens')->where('tenant_id', $r->user()->tenant_id)->where('family_account_id', $a->family_account_id)->where('child_id', $a->child_id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            Audit::record('revoke-family', 'child', $a->child_id);
        });

        return back();
    }

    public function publish(Request $r, string $type, int $record)
    {
        Gate::authorize('manage-nursery');
        abort_unless(in_array($type, ['care', 'incident']), 404);
        $d = $r->validate(['visible' => 'required|boolean']);
        $table = $type === 'care' ? 'daily_logs' : 'incidents';
        $q = DB::table($table)->where('tenant_id', $r->user()->tenant_id)->where('id', $record);
        abort_unless((clone $q)->exists(), 404);
        $q->update(['family_visible' => $d['visible'], 'updated_at' => now()]);
        Audit::record('family-visibility', $table, $record, $d['visible'] ? 'Published to authorized families' : 'Hidden from families');

        return back();
    }

    public function reply(Request $r, FamilyWorkspace $s)
    {
        Gate::authorize('manage-nursery');
        $d = $r->validate(['family_account_id' => 'required|integer', 'child_id' => 'required|integer', 'body' => 'required|string|max:4000']);
        $p = FamilyAccount::where('tenant_id', $r->user()->tenant_id)->where('active', true)->findOrFail($d['family_account_id']);
        $s->child($p, $d['child_id']);
        DB::table('family_messages')->insert($d + ['tenant_id' => $p->tenant_id, 'staff_id' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back();
    }

    public function announcement(Request $r)
    {
        Gate::authorize('manage-nursery');
        $d = $r->validate(['title' => 'required|string|max:150', 'body' => 'required|string|max:4000']);
        DB::table('family_announcements')->insert($d + ['tenant_id' => $r->user()->tenant_id, 'created_by' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back();
    }

    public function task(Request $r)
    {
        Gate::authorize('manage-nursery');
        $d = $r->validate(['child_id' => 'required|integer', 'title' => 'required|string|max:200', 'due_date' => 'nullable|date_format:Y-m-d']);
        Child::findOrFail($d['child_id']);
        DB::table('family_tasks')->insert($d + ['tenant_id' => $r->user()->tenant_id, 'created_by' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back();
    }
}
