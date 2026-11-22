<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassroomController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:200',
        ]);

        $classroom = Classroom::create($data);
        Audit::record('create', 'classroom', $classroom->id, $classroom->name);

        return redirect()->route('children.index');
    }
}
