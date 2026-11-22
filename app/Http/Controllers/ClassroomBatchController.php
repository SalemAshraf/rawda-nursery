<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ClassroomBatchController extends Controller
{
    private const FIELDS = ['meals', 'naps', 'mood', 'activities', 'notes'];

    public function index(Request $r)
    {
        $d = $r->validate(['classroom_id' => 'nullable|integer', 'date' => 'nullable|date_format:Y-m-d']);
        $date = $d['date'] ?? $r->user()->tenant->today();
        $room = isset($d['classroom_id']) ? Classroom::findOrFail($d['classroom_id']) : null;
        $children = $room ? Child::where('classroom_id', $room->id)->where('status', 'active')->orderBy('first_name')->get() : collect();
        $logs = DailyLog::whereIn('child_id', $children->pluck('id'))->whereDate('date', $date)->get()->keyBy('child_id');
        $attendance = Attendance::whereIn('child_id', $children->pluck('id'))->whereDate('date', $date)->get()->keyBy('child_id');

        return Inertia::render('Classrooms/Batch', [
            'rooms' => Classroom::orderBy('name')->get(['id', 'name']), 'classroomId' => $room?->id, 'date' => $date, 'today' => $r->user()->tenant->today(), 'canReplace' => $r->user()->manages(),
            'children' => $children->map(fn ($c) => ['id' => $c->id, 'name' => $c->fullName(), 'has_log' => $logs->has($c->id), 'checked_in' => (bool) $attendance->get($c->id)?->check_in_at, 'checked_out' => (bool) $attendance->get($c->id)?->check_out_at]),
        ]);
    }

    private function state(array $d, bool $lock = false): array
    {
        $q = Child::whereIn('id', $d['child_ids'])->orderBy('id');
        if ($lock) {
            $q->lockForUpdate();
        }$children = $q->get();
        if ($children->count() !== count($d['child_ids']) || $children->contains(fn ($c) => $c->classroom_id != $d['classroom_id'] || $c->status !== 'active')) {
            throw ValidationException::withMessages(['child_ids' => 'Choose current active children from this classroom only.']);
        }
        $q = ($d['kind'] === 'care' ? DailyLog::query() : Attendance::query())->whereIn('child_id', $d['child_ids'])->whereDate('date', $d['date'])->orderBy('child_id');
        if ($lock) {
            $q->lockForUpdate();
        }$records = $q->get();
        if ($records->count() !== $records->pluck('child_id')->unique()->count()) {
            throw ValidationException::withMessages(['date' => 'Duplicate records need individual review before a batch can be applied.']);
        }
        $records = $records->keyBy('child_id');
        if ($d['kind'] === 'checkin' && $records->contains(fn ($v) => (bool) $v->check_out_at)) {
            throw ValidationException::withMessages(['child_ids' => 'A selected child has already left. Review that child individually.']);
        }
        $hash = hash('sha256', json_encode([$children->map(fn ($c) => $c->only('id', 'classroom_id', 'first_name', 'last_name', 'status'))->values(), $records->map(fn ($v) => $v->getAttributes())->sortKeys()->all()]));

        return [$children, $records, $hash];
    }

    private function changes(array $d, $record): array
    {
        if ($d['kind'] === 'checkin') {
            return $record?->check_in_at ? [] : ['check_in_at' => 'present'];
        }
        $changes = [];
        foreach ($d['fields'] as $key => $value) {
            $before = $record?->$key ?? '';
            if ((($d['replace_existing'] ?? false) || trim((string) $before) === '') && (string) $before !== $value) {
                $changes[$key] = $value;
            }
        }

        return $changes;
    }

    public function preview(Request $r)
    {
        $d = $r->validate(['classroom_id' => 'required|integer', 'kind' => 'required|in:care,checkin', 'date' => 'required|date_format:Y-m-d|before_or_equal:'.$r->user()->tenant->today(), 'child_ids' => 'required|array|min:1|max:100', 'child_ids.*' => 'required|integer|distinct', 'replace_existing' => 'sometimes|boolean', 'fields' => 'required_if:kind,care|array:meals,naps,mood,activities,notes', 'fields.meals' => 'nullable|string|max:500', 'fields.naps' => 'nullable|string|max:500', 'fields.mood' => 'nullable|string|max:100', 'fields.activities' => 'nullable|string|max:1000', 'fields.notes' => 'nullable|string|max:2000']);
        Classroom::findOrFail($d['classroom_id']);
        $d['child_ids'] = array_map('intval', $d['child_ids']);
        sort($d['child_ids']);
        $d['fields'] = array_filter($d['fields'] ?? [], fn ($v) => is_string($v) && trim($v) !== '');
        if ($d['kind'] === 'care' && ! $d['fields']) {
            throw ValidationException::withMessages(['fields' => 'Enter at least one care update. Empty fields never erase existing text.']);
        }
        abort_if(($d['replace_existing'] ?? false) && ! $r->user()->manages(), 403);
        if ($d['kind'] === 'checkin' && $d['date'] !== $r->user()->tenant->today()) {
            throw ValidationException::withMessages(['date' => 'Bulk arrival is available for today only.']);
        }
        [$children,$records,$hash] = $this->state($d);
        $id = (string) Str::uuid();
        $rows = $children->map(function ($c) use ($d, $records) {
            $record = $records->get($c->id);
            $changes = $this->changes($d, $record);

            return ['id' => $c->id, 'name' => $c->fullName(), 'changes' => collect($changes)->map(fn ($v, $key) => ['field' => $key, 'before' => $record?->$key ?? '', 'after' => $v])->values(), 'unchanged' => ! $changes];
        });
        DB::table('classroom_batches')->insert(['id' => $id, 'tenant_id' => $r->user()->tenant_id, 'user_id' => $r->user()->id, 'kind' => $d['kind'], 'payload' => json_encode($d), 'fingerprint' => $hash, 'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['id' => $id, 'rows' => $rows, 'changed_count' => $rows->where('unchanged', false)->count(), 'unchanged_count' => $rows->where('unchanged', true)->count(), 'expires_in' => 600]);
    }

    public function apply(Request $r, string $batch)
    {
        $r->validate(['confirmed' => 'accepted']);
        $result = DB::transaction(function () use ($r, $batch) {
            $b = DB::table('classroom_batches')->where('tenant_id', $r->user()->tenant_id)->where('user_id', $r->user()->id)->where('id', $batch)->lockForUpdate()->first();
            abort_unless($b, 404);
            if ($b->status === 'applied') {
                return json_decode($b->result, true);
            }
            abort_if(now()->gte($b->expires_at), 410, 'This preview expired. Review a fresh preview.');
            $d = json_decode($b->payload, true);
            abort_if(($d['replace_existing'] ?? false) && ! $r->user()->manages(), 403);
            if ($d['kind'] === 'checkin') {
                abort_unless($d['date'] === $r->user()->tenant->today(), 409, 'The nursery day changed. Review a new preview.');
            }
            [$children,$records,$hash] = $this->state($d, true);
            abort_unless(hash_equals($b->fingerprint, $hash), 409, 'Records changed after the preview. Review a new preview before saving.');
            $changed = 0;
            foreach ($children as $c) {
                $record = $records->get($c->id);
                $values = $this->changes($d, $record);
                if (! $values) {
                    continue;
                }
                if ($d['kind'] === 'care') {
                    $record ??= new DailyLog(['child_id' => $c->id, 'date' => $d['date']]);
                    $record->fill($values + ['created_by' => $r->user()->id, 'family_visible' => false])->save();
                } else {
                    $record ??= new Attendance(['child_id' => $c->id, 'date' => $d['date']]);
                    $record->fill(['check_in_at' => now($r->user()->tenant->timezone), 'checked_in_by' => $r->user()->id])->save();
                }
                $changed++;
            }
            $result = ['id' => $b->id, 'saved' => true, 'changed_count' => $changed, 'unchanged_count' => $children->count() - $changed];
            DB::table('classroom_batches')->where('id', $b->id)->update(['status' => 'applied', 'result' => json_encode($result), 'applied_at' => now(), 'updated_at' => now()]);
            Audit::record('classroom-batch', $d['kind'], $d['classroom_id'], $changed.' child records updated for '.$d['date'].'; preview '.$b->id);

            return $result;
        }, 3);

        return response()->json($result);
    }
}
