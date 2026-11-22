<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $date = $request->validate(['date' => 'nullable|date'])['date'] ?? $tenant->today();

        $children = Child::with('classroom', 'activePickupPersons')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $rows = Attendance::whereDate('date', $date)->get()->keyBy('child_id');

        return Inertia::render('Attendance/Index', [
            'date' => $date,
            'isToday' => $date === $tenant->today(),
            'children' => $children->map(function (Child $c) use ($rows) {
                $a = $rows->get($c->id);

                return [
                    'id' => $c->id,
                    'name' => $c->fullName(),
                    'classroom' => $c->classroom?->name,
                    'allergies' => $c->allergies,
                    'check_in_at' => $a?->check_in_at?->format('H:i'),
                    'check_out_at' => $a?->check_out_at?->format('H:i'),
                    'picked_up_by' => $a?->pickupPerson?->name,
                    'pickup_persons' => $c->activePickupPersons->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
                ];
            }),
        ]);
    }

    public function checkIn(Request $request, Child $child): RedirectResponse
    {
        abort_unless($child->status === 'active', 422);
        $tenant = $request->user()->tenant;
        $today = $tenant->today();

        $attendance = Attendance::where('child_id', $child->id)->whereDate('date', $today)->first()
            ?? new Attendance(['child_id' => $child->id, 'date' => $today]);
        if ($attendance->check_in_at === null) {
            $attendance->fill([
                'check_in_at' => now($tenant->timezone),
                'checked_in_by' => $request->user()->id,
            ])->save();
            Audit::record('check-in', 'child', $child->id, $child->fullName());
        }

        return redirect()->route('attendance.index');
    }

    public function checkOut(Request $request, Child $child): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $today = $tenant->today();

        $data = $request->validate([
            'pickup_person_id' => 'required|integer',
        ]);

        // Safety boundary: checkout is only possible to an ACTIVE pickup person
        // authorized for THIS child. The system records which authorized person
        // was presented at the door; staff remain responsible for verifying the
        // person's identity physically. A guardian relationship alone never
        // satisfies this check.
        $person = $child->activePickupPersons()->whereKey($data['pickup_person_id'])->first();
        throw_unless($person, ValidationException::withMessages([
            'pickup_person_id' => 'This person is not an active authorized pickup for this child.',
        ]));

        $attendance = Attendance::where('child_id', $child->id)->whereDate('date', $today)->first();
        throw_unless($attendance && $attendance->check_in_at, ValidationException::withMessages([
            'pickup_person_id' => 'The child is not checked in today.',
        ]));
        throw_if($attendance->check_out_at, ValidationException::withMessages([
            'pickup_person_id' => 'The child is already checked out.',
        ]));

        $attendance->fill([
            'check_out_at' => now($tenant->timezone),
            'checked_out_by' => $request->user()->id,
            'pickup_person_id' => $person->id,
        ])->save();
        Audit::record('check-out', 'child', $child->id, $child->fullName().' to '.$person->name);

        return redirect()->route('attendance.index');
    }
}
