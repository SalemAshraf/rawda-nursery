<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:manage-nursery')];
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in([...Admission::OPEN_STATUSES, 'enrolled'])]]);
        $rows = Admission::with('classroom')->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('first_name', 'like', '%'.$s.'%')->orWhere('last_name', 'like', '%'.$s.'%')->orWhere('guardian_name', 'like', '%'.$s.'%')->orWhere('guardian_phone', 'like', '%'.$s.'%')))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($a) => [
                'id' => $a->id, 'first_name' => $a->first_name, 'last_name' => $a->last_name, 'dob' => $a->dob?->toDateString(),
                'guardian_name' => $a->guardian_name, 'guardian_phone' => $a->guardian_phone, 'guardian_relation' => $a->guardian_relation,
                'classroom_id' => $a->classroom_id, 'classroom' => $a->classroom?->name, 'status' => $a->status, 'follow_up_on' => $a->follow_up_on?->toDateString(),
                'notes' => $a->notes, 'child_id' => $a->child_id,
            ]);

        return Inertia::render('Admissions/Index', [
            'rows' => $rows, 'filters' => $filters, 'today' => $request->user()->tenant->today(),
            'counts' => Admission::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'classrooms' => Classroom::withCount(['children' => fn ($q) => $q->where('status', 'active')])->orderBy('name')->get(['id', 'name', 'capacity']),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100',
            'dob' => 'nullable|date|before_or_equal:'.$request->user()->tenant->today(),
            'guardian_name' => 'required|string|max:150', 'guardian_phone' => 'required|string|max:40', 'guardian_relation' => 'required|string|max:60',
            'classroom_id' => 'nullable|integer', 'follow_up_on' => 'nullable|date', 'notes' => 'nullable|string|max:2000',
            'status' => ['sometimes', 'required', Rule::in(Admission::OPEN_STATUSES)],
        ]);
        if (! empty($data['classroom_id'])) {
            Classroom::findOrFail($data['classroom_id']);
        }

        return $data;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $admission = Admission::create($data + ['status' => 'inquiry']);
        Audit::record('create-admission', 'admission', $admission->id, 'Admission inquiry recorded');

        return back();
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($admission, $data) {
            $record = Admission::lockForUpdate()->findOrFail($admission->id);
            throw_if($record->child_id !== null, ValidationException::withMessages(['status' => 'This inquiry is already enrolled. Edit the child profile instead.']));
            $record->update($data);
            Audit::record('update-admission', 'admission', $record->id, 'Admission details or stage updated');
        });

        return back();
    }

    public function enroll(Request $request, Admission $admission): RedirectResponse
    {
        $data = $request->validate(['classroom_id' => 'required|integer', 'confirmed' => 'required|accepted']);
        $childId = DB::transaction(function () use ($request, $admission, $data) {
            $record = Admission::lockForUpdate()->findOrFail($admission->id);
            // Repeated clicks and retries return the same child, never duplicate enrollment.
            if ($record->child_id) {
                return $record->child_id;
            }
            throw_if($record->status === 'closed', ValidationException::withMessages(['status' => 'Reopen this inquiry before enrolling.']));
            $room = Classroom::findOrFail($data['classroom_id']);
            $child = Child::create(['first_name' => $record->first_name, 'last_name' => $record->last_name, 'dob' => $record->dob,
                'classroom_id' => $room->id, 'status' => 'active', 'enrolled_at' => $request->user()->tenant->today()]);
            $guardian = Guardian::create(['name' => $record->guardian_name, 'phone' => $record->guardian_phone, 'relation' => $record->guardian_relation, 'email' => '']);
            $child->guardians()->attach($guardian->id, ['is_primary' => true]);
            // A contact relationship is NOT permission to collect a child. Pickup authorization stays separate.
            $record->update(['status' => 'enrolled', 'child_id' => $child->id, 'classroom_id' => $room->id, 'follow_up_on' => null]);
            Audit::record('enroll-admission', 'child', $child->id, 'Created from inquiry #'.$record->id.'; pickup authorization still required');

            return $child->id;
        });

        return redirect()->route('children.show',$childId)->with('success','Enrollment saved. Review allergy details and authorize pickup people before the first day.');
    }
}
