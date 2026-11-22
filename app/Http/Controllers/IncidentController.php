<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Incident;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Incidents/Index', [
            'incidents' => Incident::with('child', 'reporter')->orderByDesc('occurred_at')->limit(100)->get()
                ->map(fn (Incident $i) => [
                    'id' => $i->id,
                    'child' => $i->child->fullName(),
                    'child_id' => $i->child_id,
                    'occurred_at' => $i->occurred_at->toDateTimeString(),
                    'severity' => $i->severity,
                    'description' => $i->description,
                    'reported_by' => $i->reporter->name,
                    'acknowledged' => $i->guardian_acknowledged_at !== null,
                    'acknowledged_by' => $i->guardian_acknowledged_by,
                ]),
            'canManage' => Gate::allows('manage-nursery'),
        ]);
    }

    public function store(Request $request, Child $child): RedirectResponse
    {
        $data = $request->validate([
            'occurred_at' => 'required|date',
            'severity' => 'required|in:'.implode(',', Incident::SEVERITIES),
            'description' => 'required|string|max:5000',
        ]);

        $incident = $child->incidents()->create($data + ['reported_by' => $request->user()->id]);
        Audit::record('report-incident', 'child', $child->id, $incident->severity);

        return redirect()->route('children.show', $child);
    }

    public function acknowledge(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        $data = $request->validate([
            'guardian_name' => 'required|string|max:150',
        ]);

        // v1 has no guardian login: staff record that a named guardian
        // acknowledged the incident offline (in person or by phone).
        abort_unless($incident->guardian_acknowledged_at === null, 422);
        $incident->update([
            'guardian_acknowledged_at' => now($request->user()->tenant->timezone),
            'guardian_acknowledged_by' => $data['guardian_name'],
        ]);
        Audit::record('ack-incident', 'incident', $incident->id, $data['guardian_name']);

        return back();
    }
}
