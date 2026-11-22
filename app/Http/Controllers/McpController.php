<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Invoice;
use App\Models\OauthToken;
use App\Models\Payment;
use App\Models\User;
use App\Support\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class McpController extends Controller
{
    public function handle(Request $r)
    {
        $unauthorized = fn () => response()->json(['error' => 'unauthorized'], 401, ['WWW-Authenticate' => 'Bearer resource_metadata="'.OauthController::base($r).'/.well-known/oauth-protected-resource", scope="nursery:read"', 'Cache-Control' => 'no-store']);
        $raw = (string) $r->bearerToken();
        $token = $raw ? OauthToken::where('access_hash', hash('sha256', $raw))->first() : null;
        $user = $token ? User::find($token->user_id) : null;
        if (! $token || $token->revoked_at || $token->rotated_at || $token->access_expires_at->isPast() || $token->host !== strtolower($r->getHttpHost()) || ! OauthController::eligible($user) || $user->tenant_id !== $token->tenant_id) {
            return $unauthorized();
        }
        Auth::guard('web')->setUser($user);
        $token->update(['last_used_at' => now()]);
        $msg = $r->json()->all();
        $reply = fn ($body) => response()->json($body, 200, ['Cache-Control' => 'no-store']);
        if (($msg['jsonrpc'] ?? '') !== '2.0' || ! is_string($msg['method'] ?? null)) {
            return $reply(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32600, 'message' => 'Invalid request']]);
        }
        if (isset($msg['params']) && ! is_array($msg['params'])) {
            return $reply(['jsonrpc' => '2.0', 'id' => $msg['id'] ?? null, 'error' => ['code' => -32602, 'message' => 'Invalid parameters']]);
        }
        $id = $msg['id'] ?? null;
        if ($id === null) {
            return response('', 202);
        }
        $ok = fn ($result) => $reply(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        $err = fn ($code, $message) => $reply(['jsonrpc' => '2.0', 'id' => $id, 'error' => compact('code', 'message')]);
        if ($msg['method'] === 'initialize') {
            return $ok(['protocolVersion' => in_array($msg['params']['protocolVersion'] ?? '', ['2024-11-05', '2025-03-26', '2025-06-18']) ? $msg['params']['protocolVersion'] : '2025-06-18', 'capabilities' => ['tools' => (object) []], 'serverInfo' => ['name' => 'nursery', 'version' => '1.0'], 'instructions' => 'Owner-approved aggregate nursery reports only. No child or guardian identity, health notes, pickup information, incidents or free text. Read-only: no changes or messages. Counts are operational records, not medical, legal or safeguarding advice.']);
        }
        if ($msg['method'] === 'ping') {
            return $ok((object) []);
        }
        if ($msg['method'] === 'tools/list') {
            return $ok(['tools' => $this->tools()]);
        }
        if ($msg['method'] !== 'tools/call') {
            return $err(-32601, 'Method not found');
        }
        $params = $msg['params'] ?? [];
        if (! is_array($params) || ! is_string($params['name'] ?? null) || (isset($params['arguments']) && ! is_array($params['arguments']))) {
            return $err(-32602, 'Invalid parameters');
        }
        $name = $params['name'];
        $args = $params['arguments'] ?? [];
        if (! in_array($name, array_column($this->tools(), 'name'))) {
            return $err(-32602, 'Unknown read-only tool');
        }
        $allowed = match ($name) {
            'attendance_summary' => ['date'], 'fees_summary' => ['from', 'to'], default => []
        };
        if (array_diff(array_keys($args), $allowed)) {
            return $err(-32602, 'Unsupported argument');
        }
        $v = Validator::make($args, ['date' => 'sometimes|required|date_format:Y-m-d', 'from' => 'sometimes|required|date_format:Y-m-d', 'to' => 'sometimes|required|date_format:Y-m-d']);
        if ($v->fails()) {
            return $err(-32602, 'Use calendar dates in YYYY-MM-DD format');
        }
        $tenant = $user->tenant;
        $tid = $tenant->id;
        // Explicit tenant conditions remain even though the web guard also scopes models.
        $q = fn ($model) => $model::withoutGlobalScopes()->where('tenant_id', $tid);
        $today = $tenant->today();
        $data = match ($name) {
            'attendance_summary' => (function () use ($q, $args, $today, $tenant) {
                $date = $args['date'] ?? $today;
                $base = $q(Attendance::class)->where('date', $date)->whereNotNull('check_in_at');

                return ['date' => $date, 'timezone' => $tenant->timezone, 'active_enrollment_now' => $q(Child::class)->where('status', 'active')->count(), 'arrivals' => (clone $base)->count(), 'departures' => (clone $base)->whereNotNull('check_out_at')->count(), 'still_on_site_for_date' => (clone $base)->whereNull('check_out_at')->count()];
            })(),
            'classroom_occupancy' => ['as_of' => $today, 'classrooms' => $q(Classroom::class)->count(), 'entered_capacity_total' => (int) $q(Classroom::class)->sum('capacity'), 'active_children_assigned' => $q(Child::class)->where('status', 'active')->whereNotNull('classroom_id')->count(), 'active_children_unassigned' => $q(Child::class)->where('status', 'active')->whereNull('classroom_id')->count(), 'note' => 'Aggregate entered capacity, not licensed staffing or legal compliance. No room names or child identities.'],
            'admissions_summary' => ['as_of' => $today, 'counts' => $q(Admission::class)->whereIn('status', ['inquiry', 'tour', 'waitlist', 'closed', 'enrolled'])->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'), 'followups_due_count' => $q(Admission::class)->whereIn('status', ['inquiry', 'tour', 'waitlist'])->whereDate('follow_up_on', '<=', $today)->count()],
            'fees_summary' => $this->fees($q, $args, $today, $tenant),
        };
        if ($data === null) {
            return $err(-32602, 'Choose a chronological range of at most 366 days');
        }
        Audit::record('ai.read', 'ai', null, $name);

        return $ok(['content' => [['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]]]);
    }

    private function tools(): array
    {
        $schema = fn ($props = []) => ['type' => 'object', 'properties' => (object) $props, 'additionalProperties' => false];
        $date = ['type' => 'string', 'format' => 'date', 'description' => 'YYYY-MM-DD'];
        $tool = fn ($name, $description, $props = []) => ['name' => $name, 'description' => $description, 'inputSchema' => $schema($props), 'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false]];

        return [
            $tool('attendance_summary', 'Attendance totals on a nursery-local date, plus clearly labeled current enrollment. No identities.', ['date' => $date]),
            $tool('classroom_occupancy', 'Current aggregate classroom capacity and enrollment counts. No room or child names.'),
            $tool('admissions_summary', 'Current inquiry, visit, waitlist and due-follow-up counts. No family details.'),
            $tool('fees_summary', 'Manual fee totals for a bounded range, plus separately labeled current outstanding fees. Two-decimal ledger, not payment processing.', ['from' => $date, 'to' => $date]),
        ];
    }

    private function fees($q, array $args, string $today, $tenant): ?array
    {
        $from = CarbonImmutable::parse($args['from'] ?? substr($today, 0, 7).'-01', $tenant->timezone);
        $to = CarbonImmutable::parse($args['to'] ?? $today, $tenant->timezone);
        if ($to->lt($from) || $from->diffInDays($to) > 365) {
            return null;
        }
        $payments = $q(Payment::class)->whereDate('paid_at', '>=', $from->toDateString())->whereDate('paid_at', '<=', $to->toDateString());
        // Relation queries also use the authenticated tenant scope; malformed cross-tenant payments cannot reduce another tenant's balance.
        $outstanding = $q(Invoice::class)->where('status', 'sent')->withSum(['payments' => fn ($p) => $p->where('tenant_id', $tenant->id)], 'amount_minor')->get()->sum(fn ($i) => max(0, $i->amount_minor - (int) $i->payments_sum_amount_minor));
        $fmt = fn ($n) => number_format((int) $n / 100, 2, '.', '');

        return ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'payment_date_basis' => 'stored UTC calendar date, matching reports', 'currency' => $tenant->currency, 'collected' => $fmt($payments->sum('amount_minor')), 'issued' => $fmt($q(Invoice::class)->whereIn('status', ['sent', 'paid'])->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])->sum('amount_minor')), 'outstanding_now' => $fmt($outstanding), 'outstanding_as_of' => $today, 'precision' => 'two decimal ledger; not ISO precision support for three-decimal currencies'];
    }
}
