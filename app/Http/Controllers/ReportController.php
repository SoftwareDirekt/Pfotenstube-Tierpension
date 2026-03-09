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
        if(!General::permissions('Verkaufsbericht'))
        {
            return to_route('admin.settings');
        }

        $request->validate([
            'year' => 'nullable|integer|min:2000|max:' . (int) now()->addYears(5)->year,
            'month' => 'nullable|integer|min:1|max:12',
            'direction' => 'nullable|in:prev,next'
        ]);

        $baseYear = (int) $request->input('year', now()->year);
        $baseMonth = (int) $request->input('month', now()->month);
        $currentDate = Carbon::create($baseYear, $baseMonth, 1);

        $direction = $request->input('direction');
        if ($direction === 'next') {
            $currentDate = $currentDate->copy()->addMonthNoOverflow();
        } elseif ($direction === 'prev') {
            $currentDate = $currentDate->copy()->subMonthNoOverflow();
        }

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        $paymentsByDate = Payment::selectRaw('DATE(created_at) as payment_date, SUM(received_amount) as total_amount')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('received_amount', '>', 0)
            ->where('status', '!=', 0)
            ->groupBy('payment_date')
            ->orderBy('payment_date')
            ->get()
            ->pluck('total_amount', 'payment_date');

        $salesData = [];
        $weeklyTotals = [];
        $monthlyTotal = 0.0;

        $dateCursor = $startOfMonth->copy();
        while ($dateCursor->lte($endOfMonth)) {
            $weekLabel = 'KW ' . $dateCursor->isoWeek();
            $dateKey = $dateCursor->toDateString();
            $amount = (float) ($paymentsByDate[$dateKey] ?? 0);

            if (!isset($salesData[$weekLabel])) {
                $salesData[$weekLabel] = [];
                $weeklyTotals[$weekLabel] = 0.0;
            }

            $salesData[$weekLabel][] = [
                'date' => $dateCursor->format('d.m.Y'),
                'amount' => $amount,
            ];

            $weeklyTotals[$weekLabel] += $amount;
            $monthlyTotal += $amount;

            $dateCursor->addDay();
        }

        $displayMonth = $currentDate->copy()->locale('de')->translatedFormat('F Y');

        // YTD summary (January 1st until today) for small-business turnover tracking
        $today = now();
        $ytdYear = (int) $today->year;
        $startOfYear = $today->copy()->startOfYear()->startOfDay();
        $endOfToday = $today->copy()->endOfDay();

        $ytdByMonthRaw = Payment::selectRaw('MONTH(created_at) as month_num, SUM(received_amount) as total_amount')
            ->whereBetween('created_at', [$startOfYear, $endOfToday])
            ->where('received_amount', '>', 0)
            ->where('status', '!=', 0)
            ->groupBy('month_num')
            ->orderBy('month_num')
            ->get()
            ->pluck('total_amount', 'month_num');

        $monthLabels = [
            1 => 'Jänner',
            2 => 'Februar',
            3 => 'März',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember',
        ];

        $ytdMonthlyTotals = [];
        $ytdTotal = 0.0;
        for ($m = 1; $m <= (int) $today->month; $m++) {
            $monthTotal = (float) ($ytdByMonthRaw[$m] ?? 0);
            $ytdMonthlyTotals[] = [
                'month' => $monthLabels[$m],
                'total' => $monthTotal,
            ];
            $ytdTotal += $monthTotal;
        }

        return view('admin.reports.maintwo', [
            'currentYear' => $currentDate->year,
            'currentMonth' => $currentDate->month,
            'salesData' => $salesData,
            'weeklyTotals' => $weeklyTotals,
            'monthlyTotal' => $monthlyTotal,
            'deMonth' => $displayMonth,
            'ytdYear' => $ytdYear,
            'ytdMonthlyTotals' => $ytdMonthlyTotals,
            'ytdTotal' => $ytdTotal,
        ]);
    }


}
