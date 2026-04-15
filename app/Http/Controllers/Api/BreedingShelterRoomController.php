<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiJsonResponses;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomCapacityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * BreedingShelterRoomController is responsible for syncing data between Tierpension and the Breeding Shelter.
 * It is used to get the room list and occupancy data from the Breeding Shelter and sync it to Tierpension.
 * It is used to sync the occupancy data from the Breeding Shelter to Tierpension.
 */
class BreedingShelterRoomController extends Controller
{
    use ApiJsonResponses;

    public function __construct(
        private readonly RoomCapacityService $roomCapacity
    ) {}

    public function index(): JsonResponse
    {
        $rooms = Room::query()->orderBy('order')->orderBy('id')->get();

        $data = $rooms->map(function (Room $room) {
            $boarding = $this->roomCapacity->boardingCount($room);
            $breeding = $this->roomCapacity->breedingShelterSlots($room);
            $capacity = $this->roomCapacity->capacityInt($room);
            $used = $boarding + $breeding;
            $remaining = max(0, $capacity - $used);

            return [
                'id' => $room->id,
                'number' => $room->number,
                'type' => $room->type,
                'capacity' => $capacity,
                'order' => $room->order,
                'status' => $room->status,
                'room_condition' => $room->room_condition,
                'cleaning_status' => $room->cleaning_status ?? null,
                'boarding_occupancy' => $boarding,
                'breeding_shelter_occupancy' => $breeding,
                'slots_used' => $used,
                'remaining_capacity' => $remaining,
                'updated_at' => $room->updated_at?->toIso8601String(),
            ];
        });

        return $this->successResponse('OK', ['rooms' => $data]);
    }

    public function syncOccupancy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'occupancies' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validierungsfehler.', $validator->errors(), 422);
        }

        $occupancies = $request->input('occupancies', []);

        try {
            DB::transaction(function () use ($occupancies): void {
                foreach ($occupancies as $roomId => $payloadData) {
                    $roomId = (int) $roomId;
                    
                    // Handle both old format (integer) or new format (array with count + animals)
                    $count = is_array($payloadData) ? (int) ($payloadData['count'] ?? 0) : (int) $payloadData;
                    $animals = is_array($payloadData) && isset($payloadData['animals']) ? json_encode($payloadData['animals']) : null;
                    
                    $room = Room::query()->lockForUpdate()->find($roomId);
                    if (! $room) {
                        throw new \InvalidArgumentException("Raum {$roomId} existiert nicht.");
                    }
                    $boarding = $this->roomCapacity->boardingCount($room);
                    $capacity = $this->roomCapacity->capacityInt($room);
                    if ($boarding + $count > $capacity) {
                        throw new \InvalidArgumentException(
                            "Kapazität für Raum {$room->number} überschritten ({$boarding} Pension + {$count} Zucht > {$capacity})."
                        );
                    }
                    $room->update([
                        'breeding_shelter_occupancy' => $count,
                        'breeding_shelter_animals' => $animals
                    ]);
                }
            });
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return $this->errorResponse('Synchronisierung fehlgeschlagen: '.$e->getMessage(), null, 500);
        }

        return $this->successResponse('Belegung synchronisiert.');
    }
}
