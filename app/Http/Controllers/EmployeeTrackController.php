<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SebastianBergmann\Type\NullType;
use DB;

class EmployeeTrackController extends Controller
{
    public function index(Request $request)
    {
        $selected_month = Carbon::now()->month;
        $selected_year = Carbon::now()->year;

        if (isset($request->month) && isset($request->year)){
            $selected_month = $request->month;
            $selected_year = $request->year;
        }

        $first_day = Carbon::create($selected_year, $selected_month, 1)->toDateString();
        $last_day = Carbon::create($selected_year, $selected_month, 1)->endOfMonth()->toDateString();

        $employees = User::with(['events' => function ($query) use ($first_day, $last_day) {
            $query->whereBetween('start', [$first_day, $last_day]);
        }])
            ->where('role', 2)
            ->get();

        foreach($employees as $obj)
        {
            foreach($obj->events  as $event)
            {
                $start = new \DateTime($event->start);
                $end = new \DateTime($event->end);
                $interval = $start->diff($end);
                $hoursWorked = $interval->h + ($interval->days * 24) + ($interval->i / 60);
                $event->hours_worked = $hoursWorked;
            }
        }

        return view('admin.workingtimemeasurement.index', compact('employees', 'selected_month', 'selected_year'));
    }

    public function monatsplanShow(Request $request)
    {

        if(isset($request->month) && isset($request->action))
        {
            $month = $request->month;
            $action = $request->action;

            $currentMonth = ($action == 'next') ? Carbon::parse($month)->addMonth()->format('F Y') : Carbon::parse($month)->subMonth()->format('F Y');
        }
        else{
            $currentMonth = Carbon::now()->format('F Y');
        }


        $startOfMonth = Carbon::parse($currentMonth)->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::parse($currentMonth)->endOfMonth()->format('Y-m-d');

        $events = Event::whereIn('shift', ['morning', 'evening'])
            ->whereBetween('start', [$startOfMonth, $endOfMonth])
            ->with('user:id,name')
            ->orderBy('start')
            ->get()
            ->groupBy(function ($event) {
                return Carbon::parse($event->start)->format('F Y');
            });
        $employees = User::where('role', 2)->select('id', 'name')->get();


        // Pass data to the view
        $date = Carbon::parse($currentMonth);
        $date->locale('de');

        $deMonth = $date->monthName;

        return view('admin.employee_monatsplan', compact('employees', 'events', 'currentMonth', 'deMonth'));
    }


    private function getRandomColorFromList()
    {
        $colorCodes = [
            ['backgroundColor' => '#FF0000', 'color' => '#FFFFFF'],
            ['backgroundColor' => '#00FF00', 'color' => '#000000'],
            ];
        return $colorCodes[array_rand($colorCodes)];
    }


    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'employees' => 'required|array|min:1',
            'employees.*' => 'exists:users,id',
            'startDateTime' => 'required|date',
            'endDateTime' => 'required|date|after:startDateTime',
            'shiftType' => 'required|string|in:morning,evening',
            'title' => 'nullable|string',
        ]);

        $employees = $validated['employees'];
        $randomColor = $this->getRandomColorFromList();

        foreach ($employees as $employeeId) {
            $event = Event::create([
                'title' => $validated['title'] ?? null,
                'start' => $validated['startDateTime'],
                'end' => $validated['endDateTime'],
                'uid' => $employeeId,
                'status' => 'Arbeit',
                'backgroundColor' => $randomColor['backgroundColor'],
                'textColor' => $randomColor['color'],
                'shift' => $validated['shiftType'],
            ]);
        }

        return back()->with('success', 'Schicht erfolgreich hinzugefügt');
    }

    //check monatsplan

    public function checkEventShift(Request $request)
    {
        $userId = $request->uid;

        // Query the events table to fetch the first event with 'morning' or 'evening' shift
        $event = Event::where('uid', $userId)
            ->whereIn('shift', ['morning', 'evening'])
            ->first();

        if ($event) {
            return response()->json([
                'exists' => true,
                'event' => [
                    'start' => Carbon::parse($event->start)->format('h:i A'),  // 12-hour format with AM/PM
                    'end' => Carbon::parse($event->end)->format('h:i A')  // 12-hour format with AM/PM

                ]
            ]);
        } else {
            return response()->json([
                'exists' => false
            ]);
        }
    }
    public function workingRecordPdf(Request $request)
    {
        $selected_month = $request->month ?? Carbon::now()->month;
        $selected_year  = $request->year  ?? Carbon::now()->year;

        $start  = Carbon::createFromDate($selected_year, $selected_month, 1);
        $end    = $start->copy()->endOfMonth();
        $period = CarbonPeriod::create($start, $end);
        $days   = iterator_to_array($period);

        $employee = User::findOrFail($request->employee);

        $pdf = PDF::loadView(
            'admin.workingrecord.pdf',
            compact('days', 'employee', 'selected_month', 'selected_year')
        )
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
            ]);

        return $pdf->download("Arbeitszeit_{$selected_year}_{$selected_month}.pdf");
    }




}
