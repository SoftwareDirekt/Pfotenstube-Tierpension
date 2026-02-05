<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Dog;
use App\Models\Room;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition()
    {
        return [
            'dog_id' => Dog::factory(),
            'room_id' => Room::factory(),
            'plan_id' => Plan::factory(),
            'checkin_date' => Carbon::now()->subDays(3),
            'checkout_date' => null,
            'status' => 1, // Checked in
            'visit_counted' => false,
            'days_counted' => false,
        ];
    }

    public function checkedOut()
    {
        return $this->state(function (array $attributes) {
            return [
                'checkout_date' => Carbon::now(),
                'status' => 2,
                'visit_counted' => true,
                'days_counted' => true,
            ];
        });
    }
}
