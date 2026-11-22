<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('manage-nursery');
        $filters = $request->validate(['entity' => 'nullable|in:child,classroom,invoice,tuition_plan,incident,user', 'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $rows = AuditLog::with('user:id,name')->when($filters['entity'] ?? null, fn ($q, $v) => $q->where('entity', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('id')->paginate(25)->withQueryString()->through(fn ($r) => [
                'id' => $r->id, 'actor' => $r->user?->name, 'action' => $r->action, 'entity' => $r->entity, 'entity_id' => $r->entity_id,
                'details' => $r->details, 'when' => $r->created_at->timezone($request->user()->tenant->timezone)->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Audit/Index', ['rows' => $rows, 'filters' => $filters]);
    }
}
