<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\DailyLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyLogController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $date = $request->validate(['date' => 'nullable|date'])['date'] ?? $tenant->today();

        $children = Child::with('classroom')->where('status', 'active')->orderBy('first_name')->get();
        $logs = DailyLog::where('date', $date)->get()->keyBy('child_id');

        return Inertia::render('Logs/Index', [
            'date' => $date,
            'children' => $children->map(function (Child $c) use ($logs) {
                $log = $logs->get($c->id);

                return [
                    'id' => $c->id,
                    'name' => $c->fullName(),
                    'classroom' => $c->classroom?->name,
                    'log' => $log ? [
                        'meals' => $log->meals, 'naps' => $log->naps, 'mood' => $log->mood,
                        'activities' => $log->activities, 'notes' => $log->notes,
                    ] : null,
                ];
            }),
        ]);
    }

    public function upsert(Request $request, Child $child): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $date = $request->validate([
            'date' => 'nullable|date',
            'meals' => 'nullable|string|max:500',
            'naps' => 'nullable|string|max:500',
            'mood' => 'nullable|string|max:100',
            'activities' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);
        $date = $data['date'] ?? $tenant->today();

        DailyLog::updateOrCreate(
            ['child_id' => $child->id, 'date' => $date],
            [
                'meals' => $data['meals'] ?? '',
                'naps' => $data['naps'] ?? '',
                'mood' => $data['mood'] ?? '',
                'activities' => $data['activities'] ?? '',
                'notes' => $data['notes'] ?? '',
                'created_by' => $request->user()->id,
            ],
        );

        return redirect()->route('logs.index', ['date' => $date]);
    }
}
