<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $account = $request->user();
        $customerId = $account->customer_id;

        $reservations = Reservation::with(['dog', 'plan'])
            ->whereHas('dog', function ($query) use ($customerId): void {
                $query->where('customer_id', $customerId);
            })
            ->orderByDesc('checkin_date')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dog_id' => 'required|integer|exists:dogs,id',
            'checkin_date' => 'required|date',
            'checkout_date' => 'required|date|after_or_equal:checkin_date',
            'plan_id' => 'nullable|integer|exists:plans,id',
        ]);

        $account = $request->user();

        $dog = Dog::where('id', $data['dog_id'])
            ->where('customer_id', $account->customer_id)
            ->first();

        if (!$dog) {
            return response()->json([
                'success' => false,
                'message' => 'Dieser Hund gehoert nicht zu deinem Konto.',
            ], 403);
        }

        $checkin = Carbon::parse($data['checkin_date'])->startOfDay()->addMinutes(5);
        $checkout = Carbon::parse($data['checkout_date'])->endOfDay();

        $hasConflict = Reservation::where('dog_id', $dog->id)
            ->whereIn('status', [1, 3])
            ->where(function ($query) use ($checkin, $checkout): void {
                $query
                    ->whereBetween('checkin_date', [$checkin, $checkout])
                    ->orWhereBetween('checkout_date', [$checkin, $checkout])
                    ->orWhere(function ($q) use ($checkin, $checkout): void {
                        $q->where('checkin_date', '<=', $checkin)
                            ->where('checkout_date', '>=', $checkout);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return response()->json([
                'success' => false,
                'message' => 'Fuer den Zeitraum existiert bereits eine Reservierung.',
            ], 422);
        }

        $planId = $data['plan_id'] ?? $dog->reg_plan ?? $dog->day_plan;
        if (!$planId) {
            return response()->json([
                'success' => false,
                'message' => 'Bitte zuerst einen gueltigen Preisplan waehlen.',
            ], 422);
        }

        $reservation = Reservation::create([
            'dog_id' => $dog->id,
            'plan_id' => $planId,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => 3,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservierung wurde erstellt.',
            'reservation' => $reservation->load(['dog', 'plan']),
        ], 201);
    }
}
