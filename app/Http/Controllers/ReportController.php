<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use Carbon\Carbon;
use App\Helpers\General;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function index(Request $request)
    {
        if(!General::permissions('Verkaufsbericht'))
        {
            return to_route('admin.settings');
        }

        $pageName = "Verkaufsbericht";
        $back = "";

        $message = session('msg', '');

        $month = date('m');
        $sales = [];
        $weekly = [];
        $showWeekly = false;
        $dataSetName = [];
        $dataSetName2 = [];
        $checkout = [];
        $reportName = '';
        $linkLine = '';

        if ($request->input('type') == "monthly") {
            $year = $request->input('year');
            $dataSetName = ["\"Jänner\"", "\"Februar\"", "\"März\"", "\"April\"", "\"Mai\"", "\"Juni\"", "\"Juli\"", "\"August\"", "\"September\"", "\"Oktober\"", "\"November\"", "\"Dezember\""];
            for ($i = 1; $i <= 12; $i++) {
                $sales[$dataSetName[$i - 1]] = $this->monthlySales($i, $year);
            }

            $linkLine = '/admin/reports?search=search&month="+getNumericMonth(label)+"&year='.$year.'&type=daily';
            $reportName = "Monatsumsätze";

            $showWeekly = true;
            $weeklyReport = $this->weeklySales($year);
            for ($i = 1; $i <= 52; $i++) {
                $dataSetName2[] = $i;
                $weekly[] = $weeklyReport[$i] ?? 0;
            }
        } elseif ($request->input('type') == "daily") {
            $year = $request->input('year');
            $month = $request->input('month');
            $lastDay = date("t", strtotime("$year-$month-01"));
            for ($i = 1; $i <= $lastDay; $i++) {
                $dataSetName[] = $i;
                $sales[$i] = $this->dailySales($i, $month, $year);
                $checkout[$i] = $this->dailyCheckout($i, $month, $year);
            }
            $reportName = "Tagesumsätze";
            $linkLine = '';
        } else {
            $year = date("Y");
            for ($i = $year - 10; $i <= $year; $i++) {
                $dataSetName[] = $i;
                $sales[$i] = $this->annualSales($i);
            }
            $linkLine = '/admin/reports?search=search&year="+label+"&type=monthly';
            $reportName = "Jahresumsätze";
        }

        return view('admin.reports.main',['year' => date('Y'),'request' => $request, 'type' => 'monthly'], compact('pageName', 'back', 'message', 'sales', 'weekly', 'showWeekly', 'dataSetName', 'dataSetName2', 'reportName', 'linkLine', 'year', 'month','checkout'));
    }

    public static function monthNames($id)
    {
        $dataSetName = ["Jänner", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"];
        return $dataSetName[$id - 1];
    }

    public function annualSales($year)
    {
        $total = Payment::whereYear('created_at', $year)
            ->where('status', 1)
            ->sum('received_amount');

        return $total;
    }

    public function monthlySales($month, $year)
    {
        $total = Payment::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 1)
            ->sum('received_amount');

        return $total;
    }

    public function weeklySales($year)
    {
        $sales = Payment::selectRaw('WEEK(created_at, 3) as week_of_year, SUM(received_amount) as total_sales')
            ->whereYear('created_at', $year)
            ->where('status', 1)
            ->groupBy('week_of_year')
            ->get()
            ->pluck('total_sales', 'week_of_year');

        return $sales;
    }

    public function dailySales($day, $month, $year)
    {
        $total = Payment::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereDay('created_at', $day)
            ->where('status', 1)
            ->sum('received_amount');

        return $total;
    }

    public function dailyCheckout($day, $month, $year)
    {
        $checkoutCount = Reservation::whereYear('checkout_date', $year)
            ->whereMonth('checkout_date', $month)
            ->whereDay('checkout_date', $day)
            ->where('status', 2)
            ->count();

        return $checkoutCount;
    }

    public function sales(Request $request)
    {
        // Determine the current month based on the request
        if ($request->has('month') && $request->has('action')) {
            $month = $request->month;
            $action = $request->action;

            // Move forward or backward a month based on the action
            $currentMonth = ($action === 'next')
                ? Carbon::parse($month)->addMonth()->format('F Y')
                : Carbon::parse($month)->subMonth()->format('F Y');
        } else {
            // Default to the current month
            $currentMonth = Carbon::now()->format('F Y');
        }


        // Calculate the start and end dates of the selected month
        $startOfMonth = Carbon::parse($currentMonth)->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonth)->endOfMonth();

        // Initialize variables
        $salesData = [];
        $weeklyTotals = [];
        $monthlyTotal = 0;
        $currentWeek = 1;
        $weekData = [];
        $weeklyTotal = 0;

        // Loop through each day in the month
        $rest = false;
        for ($date = $startOfMonth; $date < $endOfMonth; $date->addDay()) {
            $formattedDate = date('d.m.Y', strtotime($date));
            $dbDate = date('Y-m-d', strtotime($date));

            $dailySales = 0;

            $dailySales_raw = Reservation::with('plan')->whereDate('checkout_date', $dbDate)
                ->where('status', '!=', 4)
                ->get();

            if(count($dailySales_raw) > 0)
            {
                foreach($dailySales_raw as $obj)
                {
                    $checkin = Carbon::parse($obj->checkin_date);
                    $checkout = Carbon::parse($obj->checkout_date);

                    $diffInDays = $checkout->diffInDays($checkin);
                    $dailySales += $diffInDays * $obj->plan->price;
                }
            }

            // Add daily sales to the current week's data
            $weekData[] = [
                'date' => $formattedDate,
                'amount' => $dailySales,
            ];
            $weeklyTotal += $dailySales;

            // Check if it's the end of the week or month
            $parsed_start_date = Carbon::parse($date);
            $endOfMonth = Carbon::parse($endOfMonth);

            if ($parsed_start_date->isSunday() || $parsed_start_date->isSameDay($endOfMonth)) {
                $salesData["KW $currentWeek"] = $weekData;
                $weeklyTotals["KW $currentWeek"] = $weeklyTotal;

                $monthlyTotal += $weeklyTotal;
                $currentWeek++;
                $weekData = [];
                $weeklyTotal = 0;
            }
        }

        // Pass data to the view
        $date = Carbon::parse($currentMonth);
        $date->locale('de');

        $deMonth = $date->monthName;

        return view('admin.reports.maintwo', [
            'currentMonth' => $currentMonth,
            'salesData' => $salesData,
            'weeklyTotals' => $weeklyTotals,
            'monthlyTotal' => $monthlyTotal,
            'deMonth' => $deMonth
        ]);
    }


}
