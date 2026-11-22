<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Classroom;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChildController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Children/Index', [
            'children' => Child::with('classroom')->orderBy('first_name')->get()
                ->map(fn (Child $c) => [
                    'id' => $c->id,
                    'name' => $c->fullName(),
                    'classroom' => $c->classroom?->name,
                    'status' => $c->status,
                    'has_allergies' => trim((string) ($c->allergies ?? '')) !== '',
                ]),
            'classrooms' => Classroom::orderBy('name')->get(['id', 'name']),
            'canManage' => Gate::allows('manage-nursery'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'allergies' => 'nullable|string|max:2000',
            'health_notes' => 'nullable|string|max:5000',
            'enrolled_at' => 'nullable|date',
        ]);

        // A classroom id from another tenant fails the exists check only if it
        // does not exist at all; enforce tenant ownership explicitly.
        if (! empty($data['classroom_id'])) {
            abort_unless(Classroom::whereKey($data['classroom_id'])->exists(), 422);
        }

        $child = Child::create($data + ['status' => 'active']);
        Audit::record('create', 'child', $child->id, $child->fullName());
        if (trim((string) ($data['health_notes'] ?? '')) !== '' || trim((string) ($data['allergies'] ?? '')) !== '') {
            Audit::record('set-health-data', 'child', $child->id, 'initial health/allergy data recorded');
        }

        return redirect()->route('children.show', $child);
    }

    public function show(Request $request, Child $child): Response
    {
        $child->load(['classroom', 'guardians', 'pickupPersons', 'incidents.reporter']);
        $canSeeHealth = Gate::allows('view-health-notes');

        return Inertia::render('Children/Show', [
            'child' => [
                'id' => $child->id,
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'name' => $child->fullName(),
                'dob' => $child->dob?->toDateString(),
                'gender' => $child->gender,
                'classroom' => $child->classroom?->name,
                'status' => $child->status,
                'enrolled_at' => $child->enrolled_at?->toDateString(),
                'allergies' => $child->allergies,
                'health_notes' => $canSeeHealth ? $child->health_notes : null,
            ],
            'guardians' => $child->guardians->map(fn ($g) => [
                'id' => $g->id, 'name' => $g->name, 'phone' => $g->phone,
                'email' => $g->email, 'relation' => $g->relation, 'is_primary' => (bool) $g->pivot->is_primary,
            ]),
            'pickupPersons' => $child->pickupPersons->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'relation' => $p->relation,
                'phone' => $p->phone, 'active' => $p->active,
            ]),
            'incidents' => $child->incidents->sortByDesc('occurred_at')->values()->map(fn ($i) => [
                'id' => $i->id, 'occurred_at' => $i->occurred_at->toDateTimeString(),
                'severity' => $i->severity, 'description' => $i->description,
                'reported_by' => $i->reporter->name,
                'acknowledged' => $i->guardian_acknowledged_at !== null,
            ]),
            'canManage' => Gate::allows('manage-nursery'),
            'canSeeHealth' => $canSeeHealth,
        ]);
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'allergies' => 'nullable|string|max:2000',
            'health_notes' => 'nullable|string|max:5000',
            'status' => 'required|in:active,archived',
        ]);

        if (! empty($data['classroom_id'])) {
            abort_unless(Classroom::whereKey($data['classroom_id'])->exists(), 422);
        }

        $healthChanged = ($child->health_notes ?? '') !== ($data['health_notes'] ?? '')
            || ($child->allergies ?? '') !== ($data['allergies'] ?? '');

        $child->update($data);
        Audit::record('update', 'child', $child->id, $child->fullName());
        if ($healthChanged) {
            Audit::record('update-health-data', 'child', $child->id, 'allergies/health notes changed');
        }

        return redirect()->route('children.show', $child);
    }
}
