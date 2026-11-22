<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Guardian;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuardianController extends Controller
{
    public function store(Request $request, Child $child): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:40',
            'email' => 'nullable|email|max:255',
            'relation' => 'required|string|max:60',
            'is_primary' => 'boolean',
        ]);

        $guardian = Guardian::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? '',
            'relation' => $data['relation'],
        ]);
        $child->guardians()->attach($guardian->id, ['is_primary' => (bool) ($data['is_primary'] ?? false)]);

        Audit::record('link-guardian', 'child', $child->id, $guardian->name.' ('.$guardian->relation.')');

        return redirect()->route('children.show', $child);
    }
}
