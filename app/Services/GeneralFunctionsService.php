<?php

namespace App\Services;

use App\Models\Dog;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GeneralFunctionsService
{
    public function totalSpace(): int
    {
        $capacity = Room::sum('capacity');
        return $capacity;
    }

    public function isDateBetweenInDatabase($givenDate, $exclude = [])
    {
        // Create the exclusion clause if applicable.
        $excludeIds = "";
        if (!empty($exclude)) {
            $excludeIds = "AND id NOT IN (" . implode(",", $exclude) . ")";
        }

        // First query.
        $query1 = "
            SELECT 1 as `cont`, `dog_id`, `id`, `status`,
                DATEDIFF(DATE(`checkout_date`), DATE('$givenDate')) + 1 AS `stay_nights`,
                DATE(`checkin_date`) as checkInDate, MONTH(`checkin_date`) as inMonth, MONTH('$givenDate') as givenMonth
            FROM `reservations`
            WHERE DATE('$givenDate') BETWEEN DATE(`checkin_date`) AND DATE(`checkout_date`)
                AND (status = 3 OR status = 1) $excludeIds
        ";

        // Execute the query.
        $result = DB::select($query1);

        if (count($result) > 0) {
            $row = (array)$result[0];

            // Check if in the same month.
            if ($row['inMonth'] == $row['givenMonth']) {
                // Second query.
                $query2 = "
                    SELECT 2 as `cont`, `dog_id`, `id`, `status`,
                        DATEDIFF(DATE(`checkout_date`), DATE(`checkin_date`)) + 1 AS `stay_nights`,
                        DATE(`checkin_date`) as checkInDate, MONTH(`checkin_date`) as inMonth, MONTH('$givenDate') as givenMonth
                    FROM `reservations`
                    WHERE DATE('$givenDate') = DATE(`checkin_date`)
                        AND (status = 3 OR status = 1) $excludeIds
                ";

                // Execute the query.
                $result2 = DB::select($query2);

                if (count($result2) > 0) {
                    return (array)$result2[0];
                } else {
                    return false;
                }
            }

            return $row;
        }

        return false;
    }

    public function getDogNameByID(int $id): string
    {
        $dog_name = Dog::find($id)?->name ?? '';
        return $dog_name.' ('.$id.')';
    }

    public function getCompatibilityByID(int $id): string
    {
        return Dog::find($id)?->compatibility ?? '';
    }

    public function totalReservations($month = 0): int
    {
        $startOfMonth = Carbon::now()->addMonths($month)->format('Y-m-01');
        $endOfMonth = Carbon::now()->addMonths($month)->format('Y-m-t');

        return Reservation::whereBetween('checkin_date', [$startOfMonth, $endOfMonth])
            ->where('status', 3)
            ->orWhere('status', 1)
            ->count();
    }
}
