<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;

class RoomCapacityService
{
    public function boardingCount(Room $room, ?int $excludeReservationId = null): int
    {
        $query = Reservation::query()
            ->where('room_id', $room->id)
            ->where('status', Reservation::STATUS_ACTIVE);

        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->count();
    }

    public function breedingShelterSlots(Room $room): int
    {
        return (int) $room->breeding_shelter_occupancy;
    }

    public function totalSlotsUsed(Room $room, ?int $excludeReservationId = null): int
    {
        return $this->boardingCount($room, $excludeReservationId) + $this->breedingShelterSlots($room);
    }

    public function capacityInt(Room $room): int
    {
        return max(0, (int) $room->capacity);
    }

    /**
     * Whether one additional boarding reservation can be placed in this room
     * (drag/drop / move / update room assignment).
     */
    public function canAcceptAdditionalBoarding(Room $room, ?int $excludeReservationId = null): bool
    {
        return $this->totalSlotsUsed($room, $excludeReservationId) < $this->capacityInt($room);
    }

    public function remainingCapacity(Room $room, ?int $excludeReservationId = null): int
    {
        return max(0, $this->capacityInt($room) - $this->totalSlotsUsed($room, $excludeReservationId));
    }
}
