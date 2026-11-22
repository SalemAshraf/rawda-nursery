<?php

namespace App\Http\Controllers;

use App\Models\OauthCode;
use App\Models\OauthToken;
use App\Models\TenantSubscription;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AiSettingsController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->isOwner(), 403);

        return Inertia::render('Ai/Index', [
            'enabled' => (bool) $r->user()->tenant->ai_enabled,
            'eligible' => OauthController::eligible($r->user()),
            'endpoint' => OauthController::base($r).'/mcp',
            'connections' => OauthToken::where('tenant_id', $r->user()->tenant_id)->whereNull('revoked_at')->whereNull('rotated_at')->where('refresh_expires_at', '>', now())->with('user:id,name')->latest('id')->get()->map(fn ($t) => ['family' => $t->family, 'owner' => $t->user?->name, 'connected_at' => $t->created_at->toIso8601String(), 'last_used_at' => $t->last_used_at?->toIso8601String()]),
        ]);
    }

    public function update(Request $r)
    {
        abort_unless($r->user()->isOwner(), 403);
        $d = $r->validate(['enabled' => 'required|boolean', 'password' => 'required|current_password:web']);
        if ($d['enabled']) {
            $r->validate(['consent' => 'accepted']);
            $s = TenantSubscription::forTenant($r->user()->tenant_id);
            abort_if($s && ! $s->writable(), 403, 'Activation is required before enabling external AI access.');
        }
        DB::transaction(function () use ($r, $d) {
            $r->user()->tenant->forceFill(['ai_enabled' => $d['enabled']])->save();
            if (! $d['enabled']) {
                OauthToken::where('tenant_id', $r->user()->tenant_id)->update(['revoked_at' => now()]);
                OauthCode::where('tenant_id', $r->user()->tenant_id)->delete();
            }
            Audit::record($d['enabled'] ? 'ai.enable' : 'ai.disable', 'ai', null, 'Owner-approved aggregate access');
        });

        return back()->with('success', 'Saved.');
    }

    public function revoke(Request $r, string $family)
    {
        abort_unless($r->user()->isOwner(), 403);
        $q = OauthToken::where('tenant_id', $r->user()->tenant_id)->where('family', $family);
        abort_unless((clone $q)->exists(), 404);
        $q->update(['revoked_at' => now()]);
        Audit::record('ai.revoke', 'ai', null, 'Connection revoked');

        return back()->with('success', 'Saved.');
    }
}
