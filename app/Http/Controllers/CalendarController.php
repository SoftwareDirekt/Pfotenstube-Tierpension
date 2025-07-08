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


    public function index(Request $request)
    {
        if (!General::permissions('Hundekalender')) {
            return to_route('admin.settings');
        }

        $uv_count = [];
        $total_count = [];

        $incrementMonth = $request->input('month', 0);
        $date = new DateTime();
        $date->modify("$incrementMonth month");
        $year = $date->format('Y');
        $month = $date->format('m');

        $formatter = new IntlDateFormatter('de_AT', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM Y');
        $monthAndYear = $formatter->format($date);

        // Table Generation
        $tableHead = "<tr><th></th>";
        $dateTime = new DateTime();
        $dateTime->setDate($year, $month, 1);

        for ($day = 1; $day <= $dateTime->format('t'); $day++) {
            $dateKey = str_pad($day, 2, '0', STR_PAD_LEFT);
            $tableHead .= "<th style='width: 25px;font-size: 14px!important;'><span class='day_heading_$day' data-toggle='tooltip' title='UV:1 V:2'>$dateKey</span></th>";
        }
        $tableHead .= "</tr>";
        $tableBody = "<tr>";
        $idsExclude = [];
        $totalSpace = $this->general->totalSpace();
        $colors = ['green', 'blue', 'red', 'yellow', 'pink', 'orange'];
        $colorIndex = 0;
        $light_date = 1;

        for ($rooms = 1; $rooms <= $totalSpace; $rooms++) {
            $tableBody .= "<tr><td style='width: 20px;font-size:14px;padding:10px'>$rooms</td>";

            for ($day = 1; $day <= $dateTime->format('t'); $day++) {
                if ($colorIndex % 5 == 0) $colorIndex = 0;
                $dateKey = str_pad($day, 2, '0', STR_PAD_LEFT);
                $isOccupiedData = $this->general->isDateBetweenInDatabase("$year-$month-$dateKey", $idsExclude);
                $bgwarning = $colors[$colorIndex];

                if (!$isOccupiedData) {
                    $tableBody .= '<td style="background-color:white;color:#999!important;font-weight:600">' . $day . '</td>';
                    continue;
                }

                $arrow = $isOccupiedData['stay_nights'] == 1 ? '+' : '--->';
                $isOccupied = $isOccupiedData['stay_nights'];
                $idsExclude[] = $isOccupiedData['id'];

                if ($isOccupiedData['cont'] == 1) {
                    $arrow = '--->';
                } else {
                    // if ($this->general->getCompatibilityByID($isOccupiedData['dog_id']) == 'UV'){
                    //     $uv_count[$day][$rooms] = "UV";
                    // }else{
                    //     $total_count[$day][$rooms] = "V";
                    // }

                    if ($isOccupiedData['stay_nights'] == 1)
                        $arrow = '+';
                    else
                        $arrow = '';
                }

                if ($this->general->getCompatibilityByID($isOccupiedData['dog_id']) === 'UV') {
                    $bgwarning = "deeppink";
                    $uv_count[$day][$rooms] = "UV";
                } else {
                    $total_count[$day][$rooms] = "V";
                }

                $bgColor = "style='background-color:$bgwarning'";
                if ($isOccupied == 1) {
                    $bgColor = 'style="background-color:' . $bgwarning . '; padding:10px; white-space: nowrap;overflow: hidden;text-overflow: ellipsis;display: inline-block;"';
                }

                $tableBody .= "<td class='text text-black' data-toggle='tooltip' data-placement='auto' title='" . $this->general->getDogNameByID($isOccupiedData['dog_id']) . "' colspan='$isOccupied' $bgColor>$arrow</td>";


                $flag = 1;
                for ($i = 1; $i <= $isOccupied; $i++) {
                    if ($this->general->getCompatibilityByID($isOccupiedData['dog_id']) == 'UV') {
                        $uv_count[$flag][$rooms] = "UV";
                    } else {
                        $total_count[$flag][$rooms] = "V";
                    }
                    $flag++;
                }

                $day += $isOccupied - 1;
            }
            $tableBody .= '</tr>';

            // Table Generation
            $tableHead = "";
            $tableHead = "<tr><th style='width: 20px;font-size:14px;'></th>";
            $dateTime = new DateTime();
            $dateTime->setDate($year, $month, 1);
            for ($day = 1; $day <= $dateTime->format('t'); $day++) {
                $dateKey = str_pad($day, 2, '0', STR_PAD_LEFT);
                $tableHead .= $this->tableHead($day, $dateKey, $uv_count, $total_count);
            }
            $tableHead .= "</tr>";
        }
        $total_reservations = $this->general->totalReservations($incrementMonth);
        return view('admin.calendar.dogs', compact('monthAndYear', 'tableHead', 'tableBody', 'incrementMonth', 'total_reservations'));
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

        $reservations = Reservation::with('dog')
            ->whereDate('checkin_date', '<=', $end)
            ->whereDate('checkout_date', '>=', $start)
            ->where('status', 3)
            ->orWhere('status', 1)
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
                    if ($matrix[$r][$d] !== null) {
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

        $total_reservations = Reservation::whereMonth('checkin_date', $current->month)
            ->orWhereMonth('checkout_date', $current->month)
            ->where('status', 3)
            ->orWhere('status', 1)
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
