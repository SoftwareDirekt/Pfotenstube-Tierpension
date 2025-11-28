<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use IntlDateFormatter;
use App\Helpers\General;
use App\Services\GeneralFunctionsService;

class CalendarController extends Controller
{
    private $general;

    public function __construct(GeneralFunctionsService $general)
    {
        $this->general = $general;
    }

    private function tableHead($day, $dateKey, $uv_count, $total_count)
    {
        $title = 'UV:' . count($uv_count[$day] ?? []) . ' V:' . count($total_count[$day] ?? []);
        $tableHead = "<th style='width: 25px;font-size:14px;padding:10px'><span class='day_heading_$day' data-toggle='tooltip' title='$title'>$dateKey</span></th>";
        return $tableHead;
    }

    public function showCalendar()
    {
        if (!General::permissions('Kalender')) {
            return to_route('admin.settings');
        }

        return view('admin.calendar.main');
    }


    public function dogsCalendar(Request $request)
    {
        if (!General::permissions('Hundekalender')) {
            return to_route('admin.settings');
        }

        $incrementMonth = (int)$request->input('month', 0);
        $current = Carbon::now()->startOfMonth()->addMonths($incrementMonth);
        $start = $current->toDateString();
        $end = $current->copy()->endOfMonth()->toDateString();
        $daysInMonth = $current->daysInMonth;

        $monthAndYear = $current->locale('de')->isoFormat('MMMM Y');

        // Fix query: Group date conditions and status conditions properly
        // Only get reservations that overlap with the month AND have status 1 or 3
        $reservations = Reservation::with('dog')
            ->where(function($query) use ($start, $end) {
                $query->whereDate('checkin_date', '<=', $end)
                      ->whereDate('checkout_date', '>=', $start);
            })
            ->whereIn('status', [1, 3])
            ->orderBy('checkin_date')
            ->get();

        [$fullMonth, $otherRes] = $reservations->partition(function ($res) use ($start, $end) {
            return $res->checkin_date <= $start && $res->checkout_date >= $end;
        });
        $reservations = $fullMonth->concat($otherRes);

        $compatTypes = ['UV', 'V', 'VJ', 'VM', 'S'];
        $compatibles = [];
        foreach (range(1, $daysInMonth) as $d) {
            $compatibles[$d] = array_fill_keys($compatTypes, 0);
        }
        foreach ($reservations as $res) {
            // Skip if dog is deleted
            if (!$res->dog) {
                continue;
            }
            
            $compat = $res->dog->compatibility;
            if (!in_array($compat, $compatTypes, true)) continue;
            $from = max($res->checkin_date, $start);
            $to = min($res->checkout_date, $end);
            for ($dt = Carbon::parse($from); $dt->lte($to); $dt->addDay()) {
                $compatibles[$dt->day][$compat]++;
            }
        }

        $months = [];
        foreach (range(0, 130) as $i) {
            $months[$i] = Carbon::now()->startOfMonth()->addMonths($i)
                ->locale('de')->isoFormat('MMMM Y');
        }

        $totalRooms = $this->general->totalSpace();

        $matrix = [];
        for ($r = 0; $r < $totalRooms; $r++) {
            $matrix[$r] = array_fill(1, $daysInMonth, null);
        }

        foreach ($reservations as $res) {
            $fromDay = Carbon::parse(max($res->checkin_date, $start))->day;
            $toDay = Carbon::parse(min($res->checkout_date, $end))->day;
            foreach ($matrix as $r => $row) {
                $free = true;
                for ($d = $fromDay; $d <= $toDay; $d++) {
                    if (isset($matrix[$r][$d]) && $matrix[$r][$d] !== null) {
                        $free = false;
                        break;
                    }
                }
                if ($free) {
                    for ($d = $fromDay; $d <= $toDay; $d++) {
                        $matrix[$r][$d] = $res;
                    }
                    break;
                }
            }
        }

        // Fix total reservations count: Only count reservations that overlap with the month
        $total_reservations = Reservation::where(function($query) use ($start, $end) {
                $query->whereDate('checkin_date', '<=', $end)
                      ->whereDate('checkout_date', '>=', $start);
            })
            ->whereIn('status', [1, 3])
            ->count();

        return view('admin.calendar.dogs2', compact(
            'monthAndYear',
            'incrementMonth',
            'months',
            'total_reservations',
            'daysInMonth',
            'compatibles',
            'matrix',
            'totalRooms'
        ));
    }

}
