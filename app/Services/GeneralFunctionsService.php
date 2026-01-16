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
}
