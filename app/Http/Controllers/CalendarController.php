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
        if (! General::permissions('Hundekalender')) {
            return to_route('admin.settings');
        }

        $increment   = (int) $request->input('month', 0);
        $current     = Carbon::now()->startOfMonth()->addMonths($increment);
        $start       = $current->copy()->startOfMonth()->toDateString();
        $end         = $current->copy()->endOfMonth()->toDateString();
        $daysInMonth = $current->daysInMonth;

        $reservations = Reservation::with('dog')
            ->whereDate('checkin_date',  '<=', $end)
            ->whereDate('checkout_date', '>=', $start)
            ->orderBy('checkin_date')
            ->get();

        $compatibles = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $compatibles[$d] = [
                'UV' => 0,
                'V'  => 0,
                'VJ'  => 0,
                'VM'  => 0,
                'S'  => 0,
            ];
        }
        foreach ($reservations as $res) {
            $compat = $res->dog->compatibility;
            $from   = max($res->checkin_date,  $start);
            $to     = min($res->checkout_date, $end);

            for ($d = Carbon::parse($from); $d->lte($to); $d->addDay()) {
                $day = $d->day;
                switch($compat){
                    case('UV'):
                        $compatibles[$day]['UV']++;
                        break;
                    case('V'):
                        $compatibles[$day]['V']++;
                        break;
                    case('VJ'):
                        $compatibles[$day]['VJ']++;
                        break;
                    case('VM'):
                        $compatibles[$day]['VM']++;
                        break;
                    case('S'):
                        $compatibles[$day]['S']++;
                        break;
                }
            }
        }

        $months = [];
        for ($i = 0; $i <= 130; $i++) {
            $m = Carbon::now()->startOfMonth()->addMonths($i);
            $months[$i] = $m->locale('de')->isoFormat('MMMM Y');
        }

        $totalReservations = Reservation::whereMonth('checkin_date', $current->month)
            ->orWhereMonth('checkout_date', $current->month)
            ->count();

        $reservationsArray = $reservations->toArray();
        $totalRooms = $this->general->totalSpace();

        if (count($reservationsArray) < $totalRooms) {
            $reservationsArray = array_pad(
                array_values($reservationsArray),
                $totalRooms,
                null
            );
        }
        return view('admin.calendar.dogs2', compact(
            'current',
            'reservationsArray',
            'daysInMonth',
            'compatibles',
            'increment',
            'months',
            'totalReservations',
            'totalRooms'
        ));
    }
}
