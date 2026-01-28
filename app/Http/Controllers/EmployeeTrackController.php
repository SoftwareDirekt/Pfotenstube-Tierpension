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

        // Filter events by status 'Arbeit' and where shift is null (exclude morning/evening shifts)
        $employees = User::with(['events' => function ($query) use ($startDate, $endDate) {
            $query->where('status', 'Arbeit')
                  ->whereNull('shift')
                  ->where('start', '>=', $startDate)
                  ->where('start', '<=', $endDate);
        }])
        ->where('role', 2)
        ->get();

        foreach ($employees as $employee) {
            foreach ($employee->events as $event) {
                $start = Carbon::parse($event->start);
                $end = $this->getEventEndTime($event);
                $event->hours_worked = $end->diffInSeconds($start) / 3600;
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
        $monthDate = Carbon::parse($currentMonth);
        $startOfMonth = $monthDate->copy()->startOfMonth()->startOfDay();
        $endOfMonth = $monthDate->copy()->endOfMonth()->endOfDay();

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
            $eventDate = Carbon::parse($event->start);
            $eventsByDayAndShift[$eventDate->day][$event->shift][] = $event;
        }

        $employees = User::where('role', 2)->orderBy('name')->get(['id', 'name']);

        return view('admin.employee_monatsplan', [
            'employees' => $employees,
            'eventsByDayAndShift' => $eventsByDayAndShift,
            'currentMonth' => $currentMonth,
            'germanMonth' => $startOfMonth->locale('de')->translatedFormat('F Y'),
            'daysInMonth' => $startOfMonth->daysInMonth,
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

        $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        $days = iterator_to_array(CarbonPeriod::create($startDate, $endDate));

        $employee = User::findOrFail($request->employee);

        $events = Event::where('uid', $employee->id)
            ->where('status', 'Arbeit')
            ->whereNull('shift')
            ->where('start', '>=', $startDate)
            ->where('start', '<=', $endDate)
            ->orderBy('start')
            ->get(['id', 'start', 'end', 'notes']);

        $totalDecimalHours = 0;
        foreach ($events as $event) {
            $start = Carbon::parse($event->start);
            $end = $this->getEventEndTime($event);
            $event->hours_worked = $end->diffInSeconds($start) / 3600;
            $totalDecimalHours += $event->hours_worked;
        }

        $dayData = [];
        $totalDaysWorked = 0;

        foreach ($days as $day) {
            $dayStart = $day->copy()->startOfDay();
            $dayEvents = $events->filter(function ($event) use ($dayStart) {
                return Carbon::parse($event->start)->isSameDay($dayStart);
            });

            $dayStartTime = null;
            $dayEndTime = null;
            $dayTotalSeconds = 0;
            $dayNotes = [];

            if ($dayEvents->count() > 0) {
                $dayStartTime = Carbon::createFromTimestamp(
                    $dayEvents->min(fn($event) => Carbon::parse($event->start)->timestamp)
                );

                $dayEndTime = Carbon::createFromTimestamp(
                    $dayEvents->max(function ($event) use ($dayStart) {
                        $end = $this->getEventEndTime($event, $dayStart);
                        return $end->timestamp;
                    })
                );

                foreach ($dayEvents as $event) {
                    $start = Carbon::parse($event->start);
                    $end = $this->getEventEndTime($event, $dayStart);
                    $dayTotalSeconds += $end->diffInSeconds($start);

                    if ($event->notes && trim($event->notes)) {
                        $dayNotes[] = trim($event->notes);
                    }
                }

                $totalDaysWorked++;
            }

            $hours = floor($dayTotalSeconds / 3600);
            $minutes = floor(($dayTotalSeconds % 3600) / 60);
            $hoursDisplay = $dayTotalSeconds > 0 ? sprintf('%02d:%02d', $hours, $minutes) : '';

            $dayData[] = [
                'date' => $day,
                'start_time' => $dayStartTime?->format('H:i'),
                'end_time' => $dayEndTime?->format('H:i'),
                'hours' => $hoursDisplay,
                'notes' => implode('; ', $dayNotes),
            ];
        }

        $totalHours = floor($totalDecimalHours);
        $totalMinutes = round(($totalDecimalHours - $totalHours) * 60);
        $totalHoursDisplay = sprintf('%02d:%02d', $totalHours, $totalMinutes);

        $pdf = Pdf::loadView('admin.workingrecord.pdf', [
            'days' => $dayData,
            'employee' => $employee,
            'selected_month' => $selectedMonth,
            'selected_year' => $selectedYear,
            'total_hours' => $totalHoursDisplay,
            'total_days_worked' => $totalDaysWorked,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $monthName = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('de')->translatedFormat('F');
        $employeeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $employee->name);
        $employeeName = preg_replace('/_+/', '_', $employeeName);
        $employeeName = trim($employeeName, '_');
        $filename = "Arbeitszeit_{$monthName}_{$selectedYear}_{$employeeName}_" . Carbon::now()->format('Ymd_His') . ".pdf";

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('X-Filename', $filename)
            ->header('Access-Control-Expose-Headers', 'X-Filename, Content-Disposition');
    }

    /**
     * Resolve current month from request
     */
    private function resolveMonth(Request $request): string
    {
        if ($request->filled('month') && $request->filled('action')) {
            $month = Carbon::parse($request->month);
            $month = $request->action === 'next' ? $month->copy()->addMonth() : $month->copy()->subMonth();
            return $month->format('F Y');
        }

        return Carbon::now()->format('F Y');
    }

    private function getEventEndTime($event, ?Carbon $dayStart = null): Carbon
    {
        if ($event->end) {
            return Carbon::parse($event->end);
        }

        $eventDate = Carbon::parse($event->start)->startOfDay();
        $targetDate = $dayStart ?? $eventDate;

        return $targetDate->isSameDay(Carbon::today())
            ? Carbon::now()
            : $targetDate->copy()->endOfDay();
    }
}
