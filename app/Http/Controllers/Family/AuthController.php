<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\FamilyAccount;
use App\Models\Guardian;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function page(Request $r)
    {
        return Inertia::render('Family/Login', ['activate' => $r->routeIs('family.activate')]);
    }

    private function authenticated(Request $r, FamilyAccount $p)
    {
        if ($r->is('api/*')) {
            $p->tokens()->where('expires_at', '<', now())->delete();

            return response()->json(['token' => $p->createToken($r->input('device_name', 'Nursery Family'), ['family'], now()->addDays(30))->plainTextToken, 'token_type' => 'Bearer', 'expires_in' => 2592000]);
        }
        Auth::guard('family')->login($p);
        $r->session()->regenerate();

        return redirect()->route('family.home');
    }

    public function login(Request $r)
    {
        $d = $r->validate(['nursery_code' => 'required|string|max:255', 'email' => 'required|email|max:255', 'password' => 'required|string|max:200', 'device_name' => 'sometimes|string|max:80']);
        $email = strtolower(trim($d['email']));
        $key = 'family-login:'.hash('sha256', $d['nursery_code'].'|'.$email.'|'.$r->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Please wait before retrying.']);
        }
        RateLimiter::hit($key, 60);
        $tenant = Tenant::where('slug', $d['nursery_code'])->first();
        $p = $tenant ? FamilyAccount::where('tenant_id', $tenant->id)->where('email', $email)->where('active', true)->first() : null;
        if (! $p || ! Hash::check($d['password'], $p->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        RateLimiter::clear($key);

        return $this->authenticated($r, $p);
    }

    public function activate(Request $r)
    {
        $d = $r->validate(['token' => 'required|string|size:64', 'email' => 'required|email|max:255', 'password' => 'required|string|min:12|max:200|confirmed', 'device_name' => 'sometimes|string|max:80']);
        $p = DB::transaction(function () use ($d) {
            $i = DB::table('family_invites')->where('token_hash', hash('sha256', $d['token']))->lockForUpdate()->first();
            $email = strtolower(trim($d['email']));
            if (! $i || $i->consumed_at || now()->gte($i->expires_at) || $i->email !== $email) {
                throw ValidationException::withMessages(['token' => 'The invitation is invalid or expired.']);
            }
            $guardian = Guardian::withoutGlobalScope('tenant')->where('tenant_id', $i->tenant_id)->whereKey($i->guardian_id)->first();
            if (! $guardian || strtolower(trim($guardian->email)) !== $email || ! DB::table('child_guardian')->where('guardian_id', $i->guardian_id)->where('child_id', $i->child_id)->exists()) {
                throw ValidationException::withMessages(['token' => 'The invitation is invalid or expired.']);
            }
            Tenant::whereKey($i->tenant_id)->lockForUpdate()->firstOrFail();
            $p = FamilyAccount::where('tenant_id', $i->tenant_id)->where('email', $email)->lockForUpdate()->first();
            if ($p && (! $p->active || ! Hash::check($d['password'], $p->password))) {
                throw ValidationException::withMessages(['password' => 'Use the existing family account password. An invitation does not reset it.']);
            }
            $p ??= FamilyAccount::create(['tenant_id' => $i->tenant_id, 'email' => $email, 'name' => $guardian->name, 'password' => $d['password']]);
            DB::table('family_access')->updateOrInsert(['family_account_id' => $p->id, 'child_id' => $i->child_id], ['tenant_id' => $i->tenant_id, 'guardian_id' => $i->guardian_id, 'pickup_person_id' => $i->pickup_person_id, 'share_billing' => $i->share_billing, 'active' => true, 'approved_by' => $i->issued_by, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('family_invites')->where('id', $i->id)->update(['consumed_at' => now()]);

            return $p;
        });

        return $this->authenticated($r, $p);
    }

    public function logout(Request $r)
    {
        if ($r->is('api/*')) {
            $r->user()->currentAccessToken()->delete();

            return response()->noContent();
        }
        Auth::guard('family')->logout();
        $r->session()->regenerateToken();

        return redirect()->route('family.login');
    }
}
