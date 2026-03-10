<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ApiJsonResponses;
use App\Models\Dog;
use App\Models\Plan;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerReservationController extends Controller
{
    use ApiJsonResponses;

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

        return $this->successResponse('Reservierungen erfolgreich geladen.', [
            'reservations' => $reservations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dog_id' => 'required|integer|exists:dogs,id',
            'checkin_date' => 'required|date',
            'checkout_date' => 'required|date|after_or_equal:checkin_date',
            'plan_id' => 'nullable|integer|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validierungsfehler bei der Reservierung.', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $account = $request->user();

        $dog = Dog::where('id', $data['dog_id'])
            ->where('customer_id', $account->customer_id)
            ->first();

        if (!$dog) {
            return $this->errorResponse(
                'Dieser Hund gehört nicht zu deinem Konto.',
                ['dog_id' => ['Ungültiger Hund für dieses Konto.']],
                403
            );
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
            return $this->errorResponse(
                'Für den Zeitraum existiert bereits eine Reservierung.',
                ['checkin_date' => ['Überschneidung mit bestehender Reservierung.']],
                422
            );
        }

        $planId = $data['plan_id'] ?? null;
        if (!$planId) {
            $plans = Plan::orderBy('id')->pluck('id')->values();
            if ($plans->count() < 2) {
                return $this->errorResponse(
                    'Es sind nicht genug Preispläne vorhanden. Bitte Tagestarif und Pensionstarif anlegen.',
                    ['plans' => ['Mindestens zwei Pläne erforderlich.']],
                    422
                );
            }

            $stayDays = Carbon::parse($data['checkin_date'])->startOfDay()
                ->diffInDays(Carbon::parse($data['checkout_date'])->startOfDay()) + 1;

            // 1 Tag => 1. Plan, >1 Tag => 2. Plan
            $planId = $stayDays === 1 ? (int) $plans[0] : (int) $plans[1];
        }

        if (!$planId) {
            return $this->errorResponse(
                'Bitte zuerst einen gültigen Preisplan wählen.',
                ['plan_id' => ['Kein gültiger Plan verfügbar.']],
                422
            );
        }

        $reservation = Reservation::create([
            'dog_id' => $dog->id,
            'plan_id' => $planId,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => 3,
        ]);

        return $this->successResponse('Reservierung wurde erstellt.', [
            'reservation' => $reservation->load(['dog', 'plan']),
        ], 201);
    }
}
