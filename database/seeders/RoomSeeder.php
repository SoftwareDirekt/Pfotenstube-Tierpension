<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            [
                'number' => 'R-01',
                'type' => 'Standard',
                'capacity' => '2',
                'order' => 1,
                'status' => 1,
            ],
            [
                'number' => 'R-02',
                'type' => 'Standard',
                'capacity' => '2',
                'order' => 2,
                'status' => 1,
            ],
            [
                'number' => 'R-03',
                'type' => 'Large',
                'capacity' => '3',
                'order' => 3,
                'status' => 1,
            ],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['number' => $room['number']],
                $room
            );
        }
    }
}
