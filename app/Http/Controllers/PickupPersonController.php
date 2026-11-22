<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\PickupPerson;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PickupPersonController extends Controller
{
    public function store(Request $request, Child $child): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'relation' => 'required|string|max:60',
            'phone' => 'nullable|string|max:40',
        ]);

        $person = $child->pickupPersons()->create([
            'name' => $data['name'],
            'relation' => $data['relation'],
            'phone' => $data['phone'] ?? '',
            'active' => true,
        ]);

        Audit::record('authorize-pickup', 'child', $child->id, $person->name.' ('.$person->relation.')');

        return redirect()->route('children.show', $child);
    }

    public function destroy(Request $request, PickupPerson $pickupPerson): RedirectResponse
    {
        Gate::authorize('manage-nursery');

        // Deactivate, never delete: past attendance rows keep referring to the
        // person who collected the child.
        $pickupPerson->update(['active' => false]);
        Audit::record('revoke-pickup', 'child', $pickupPerson->child_id, $pickupPerson->name);

        return redirect()->route('children.show', $pickupPerson->child_id);
    }
}
