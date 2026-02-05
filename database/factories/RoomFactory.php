<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition()
    {
        return [
            'number' => 'Room ' . $this->faker->unique()->numberBetween(1, 100),
            'type' => 'Standard',
            'capacity' => $this->faker->numberBetween(1, 3),
            'status' => 1, // Active
        ];
    }
}
