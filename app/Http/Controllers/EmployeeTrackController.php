<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Support\EventColor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class EmployeeTrackController extends Controller
{
    /**
     * Working time measurement index
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->month ?? Carbon::now()->month;
        $selectedYear = $request->year ?? Carbon::now()->year;

        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        $employees = User::with(['events' => function ($query) use ($startDate, $endDate) {
            $query->where('start', '>=', $startDate)
                  ->where('start', '<=', $endDate);
        }])
        ->where('role', 2)
        ->get();

        foreach ($employees as $employee) {
            foreach ($employee->events as $event) {
                $start = Carbon::parse($event->start);
                $end = Carbon::parse($event->end);
                $event->hours_worked = $start->floatDiffInHours($end);
            }
        }

        return view('admin.workingtimemeasurement.index', [
            'employees' => $employees,
            'selected_month' => $selectedMonth,
            'selected_year' => $selectedYear,
        ]);
    }

    /**
     * Monatsplan (Monthly shift plan) view
     */
    public function monatsplanShow(Request $request)
    {
        $currentMonth = $this->resolveMonth($request);
        
        // Parse month string and get proper date boundaries
        $monthDate = Carbon::parse($currentMonth);
        $startOfMonth = $monthDate->copy()->startOfMonth()->startOfDay();
        $endOfMonth = $monthDate->copy()->endOfMonth()->endOfDay();
        $daysInMonth = $startOfMonth->daysInMonth;

        // Fetch all Arbeit events for this month with shifts (only morning/evening)
        // Use proper date range with start/end of day boundaries
        $events = Event::where('status', 'Arbeit')
            ->whereIn('shift', ['morning', 'evening'])
            ->where('start', '>=', $startOfMonth)
            ->where('start', '<=', $endOfMonth)
            ->with('user:id,name')
            ->orderBy('start')
            ->get();

        // Group events by day and shift for easy access in view
        $eventsByDayAndShift = [];
        foreach ($events as $event) {
            // Ensure event is within the current month and has valid shift
            if (!$event->shift || !in_array($event->shift, ['morning', 'evening'])) {
                continue;
            }
            
            $eventDate = Carbon::parse($event->start);
            $day = $eventDate->day;
            $shift = $event->shift;
            
            // Double-check event is in the correct month
            if ($eventDate->month === $startOfMonth->month && $eventDate->year === $startOfMonth->year) {
                $eventsByDayAndShift[$day][$shift][] = $event;
            }
        }

        $employees = User::where('role', 2)->orderBy('name')->get(['id', 'name']);

        // German month name
        $germanMonth = $startOfMonth->locale('de')->translatedFormat('F Y');

        return view('admin.employee_monatsplan', [
            'employees' => $employees,
            'eventsByDayAndShift' => $eventsByDayAndShift,
            'currentMonth' => $currentMonth,
            'germanMonth' => $germanMonth,
            'daysInMonth' => $daysInMonth,
            'startOfMonth' => $startOfMonth,
        ]);
    }

    /**
     * Store a new shift event
     */
    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'shift' => 'required|in:morning,evening',
            'employees' => 'required|array|min:1',
            'employees.*' => 'exists:users,id',
        ]);

        // Validate that end time is after start time
        $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
        $end = Carbon::parse("{$validated['date']} {$validated['end_time']}");
        if ($end <= $start) {
            return back()->withErrors(['end_time' => 'Die Endzeit muss nach der Startzeit liegen.'])->withInput();
        }

        // Validate that all selected employees have role = 2 (employee)
        $employees = User::whereIn('id', $validated['employees'])
            ->where('role', 2)
            ->pluck('id');
        
        if ($employees->count() !== count($validated['employees'])) {
            return back()->withErrors(['employees' => 'Ungültige Mitarbeiter ausgewählt. Nur Mitarbeiter können hinzugefügt werden.'])->withInput();
        }

        $colors = EventColor::forStatus('Arbeit');

        foreach ($employees as $employeeId) {
            Event::create([
                'title' => null,
                'start' => $start,
                'end' => $end,
                'uid' => $employeeId,
                'status' => 'Arbeit',
                'backgroundColor' => $colors['backgroundColor'],
                'textColor' => $colors['textColor'],
                'shift' => $validated['shift'],
            ]);
        }

        return back()->with('success', 'Schicht erfolgreich hinzugefügt.');
    }

    /**
     * Update an existing shift event
     */
    public function updateEvent(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'shift' => 'required|in:morning,evening',
            'uid' => 'required|exists:users,id',
        ]);

        $colors = EventColor::forStatus('Arbeit');
        $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
        $end = Carbon::parse("{$validated['date']} {$validated['end_time']}");

        $event->update([
            'start' => $start,
            'end' => $end,
            'uid' => $validated['uid'],
            'status' => 'Arbeit',
            'backgroundColor' => $colors['backgroundColor'],
            'textColor' => $colors['textColor'],
            'shift' => $validated['shift'],
        ]);

        return back()->with('success', 'Schicht erfolgreich aktualisiert.');
    }

    /**
     * Delete a shift event
     */
    public function destroyEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return back()->with('success', 'Schicht erfolgreich gelöscht.');
    }

    /**
     * Check if user has existing shift (for prefilling times)
     */
    public function checkEventShift(Request $request)
    {
        $event = Event::where('uid', $request->uid)
            ->whereIn('shift', ['morning', 'evening'])
            ->latest('start')
            ->first();

        if ($event) {
            return response()->json([
                'exists' => true,
                'event' => [
                    'start' => Carbon::parse($event->start)->format('H:i'),
                    'end' => Carbon::parse($event->end)->format('H:i'),
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Generate working record PDF
     */
    public function workingRecordPdf(Request $request)
    {
        $selectedMonth = $request->month ?? Carbon::now()->month;
        $selectedYear = $request->year ?? Carbon::now()->year;

        $start = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
        $end = $start->copy()->endOfMonth();
        $days = iterator_to_array(CarbonPeriod::create($start, $end));

        $employee = User::findOrFail($request->employee);

        $pdf = Pdf::loadView('admin.workingrecord.pdf', [
            'days' => $days,
            'employee' => $employee,
            'selected_month' => $selectedMonth,
            'selected_year' => $selectedYear,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download("Arbeitszeit_{$selectedYear}_{$selectedMonth}.pdf");
    }

    /**
     * Resolve current month from request
     */
    private function resolveMonth(Request $request): string
    {
        if ($request->filled('month') && $request->filled('action')) {
            // Parse the month string (e.g., "October 2025")
            // Use copy() to avoid mutating the original Carbon instance
            $month = Carbon::parse($request->month);
            
            // Apply the action (next or prev) using copy() to avoid mutation
            if ($request->action === 'next') {
                $month = $month->copy()->addMonth();
            } else {
                $month = $month->copy()->subMonth();
            }
            
            return $month->format('F Y');
        }

        return Carbon::now()->format('F Y');
    }
}



