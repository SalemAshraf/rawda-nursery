<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Classrooms/Index', [
            'classrooms' => Classroom::withCount(['children' => fn ($q) => $q->where('status', 'active')])->orderBy('name')->get(['id', 'name', 'capacity']),
            'canManage' => Gate::allows('manage-nursery'),
        ]);
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        Gate::authorize('manage-nursery');
        $data = $request->validate(['name' => 'required|string|max:100', 'capacity' => 'nullable|integer|min:1|max:200']);
        $classroom->update($data);
        Audit::record('update', 'classroom', $classroom->id, $classroom->name);

        return redirect()->route('classrooms.index');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:200',
        ]);

        $classroom = Classroom::create($data);
        Audit::record('create', 'classroom', $classroom->id, $classroom->name);

        return redirect()->back();
    }
}
