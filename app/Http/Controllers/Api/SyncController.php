<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ApiJsonResponses;
use App\Models\Customer;
use App\Models\Dog;
use App\Models\Plan;
use App\Models\Reservation;
use App\Models\Visit;
use App\Models\Notification;
use App\Services\HelloCashService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    use ApiJsonResponses;

    public function __construct(
        private readonly HelloCashService $helloCashService
    ) {}

    public function syncReservation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer.remote_id' => 'required',
            'customer.name' => 'required|string',
            'customer.email' => 'required|email',
            'animal.remote_id' => 'required',
            'animal.name' => 'required|string',
            'reservation.remote_id' => 'required',
            'reservation.checkin_date' => 'required|date',
            'reservation.checkout_date' => 'required|date|after_or_equal:reservation.checkin_date',
        ]);

        if ($validator->fails()) {
            Log::warning('External sync validation failed.', $validator->errors()->toArray());
            return $this->errorResponse('Validierungsfehler: Bitte überprüfen Sie Ihre Eingaben.', $validator->errors(), 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $customerData = $request->input('customer');
                $animalData = $request->input('animal');
                $reservationData = $request->input('reservation');

                // 1. Sync Customer
                $customer = Customer::where('email', $customerData['email'])->first();
                if (!$customer) {
                    $customer = Customer::create([
                        'remote_pfotenstube_homepage_id' => $customerData['remote_id'],
                        'type' => 'Stammkunde',
                        'name' => $customerData['name'],
                        'email' => $customerData['email'],
                        'phone' => $customerData['phone'] ?? null,
                        'street' => $customerData['street'] ?? null,
                        'city' => $customerData['city'] ?? null,
                        'zipcode' => $customerData['zipcode'] ?? null,
                        'country' => $customerData['country'] ?? null,
                    ]);

                    // Initial HelloCash sync for new customer
                    $hcResult = $this->helloCashService->syncCustomer($customer);
                    if ($hcResult['success']) {
                        $customer->update(['hellocash_customer_id' => $hcResult['user_id']]);
                    }
                } else {
                    $customer->update([
                        'remote_pfotenstube_homepage_id' => $customerData['remote_id'],
                        'name' => $customerData['name'],
                        'phone' => $customerData['phone'] ?? $customer->phone,
                        'street' => $customerData['street'] ?? $customer->street,
                        'city' => $customerData['city'] ?? $customer->city,
                        'zipcode' => $customerData['zipcode'] ?? $customer->zipcode,
                        'country' => $customerData['country'] ?? $customer->country,
                    ]);
                }

                // 2. Sync Dog
                $dog = Dog::where('customer_id', $customer->id)
                    ->where('name', $animalData['name'])
                    ->first();

                if (!$dog) {
                    // Assign default plans
                    $plans = Plan::orderBy('id')->pluck('id')->values();
                    if ($plans->isEmpty()) {
                        throw new \Exception('No price plans available in Tierpension.');
                    }
                    
                    $dog = Dog::create([
                        'customer_id' => $customer->id,
                        'remote_pfotenstube_homepage_id' => $animalData['remote_id'],
                        'name' => $animalData['name'],
                        'age' => $animalData['birth_date'] ?? null,
                        'gender' => $animalData['gender'] ?? null,
                        'compatible_breed' => $animalData['breed'] ?? null,
                        'status' => 1,
                        'day_plan' => $plans[0],
                        'reg_plan' => $plans[0],
                    ]);
                    Visit::create([
                        'dog_id' => $dog->id,
                        'visits' => 0,
                        'stay' => 0
                    ]);
                } else {
                    $dog->update([
                        'remote_pfotenstube_homepage_id' => $animalData['remote_id']
                    ]);
                }

                // 3. Sync Reservation
                $checkinString = $reservationData['checkin_date'];
                $checkoutString = $reservationData['checkout_date'];
                $checkin = Carbon::parse($checkinString)->startOfDay()->addMinutes(5);
                $checkout = Carbon::parse($checkoutString)->endOfDay();

                // Conflict check
                $hasConflict = Reservation::where('dog_id', $dog->id)
                    ->whereIn('status', [Reservation::STATUS_ACTIVE, Reservation::STATUS_RESERVED])
                    ->where('remote_pfotenstube_homepage_id', '!=', $reservationData['remote_id'])
                    ->where(function ($query) use ($checkin, $checkout) {
                        $query->whereBetween('checkin_date', [$checkin, $checkout])
                            ->orWhereBetween('checkout_date', [$checkin, $checkout])
                            ->orWhere(function ($q) use ($checkin, $checkout) {
                                $q->where('checkin_date', '<=', $checkin)
                                    ->where('checkout_date', '>=', $checkout);
                            });
                    })->exists();

                if ($hasConflict) {
                     Log::warning('Reservation Date conflict for dog ID: ' . $dog->id);
                     return $this->errorResponse('Terminkonflikt für diesen Hund. Der ausgewählte Zeitraum ist bereits belegt.', null, 422);
                }

                // Reservation logic (Price Plan mapping)
                $plans = Plan::orderBy('id')->pluck('id')->values();
                $planId = $plans[0];

                $reservation = Reservation::updateOrCreate(
                    ['remote_pfotenstube_homepage_id' => $reservationData['remote_id']],
                    [
                        'dog_id' => $dog->id,
                        'plan_id' => $planId,
                        'checkin_date' => $checkin,
                        'checkout_date' => $checkout,
                        'status' => Reservation::STATUS_RESERVED,
                    ]
                );

                // Create Notification
                Notification::create([
                    'dog_id' => $dog->id,
                    'type' => 'new_reservation',
                    'title' => 'Neue Reservierung',
                    'message' => "Neue Reservierung für {$dog->name} von " . Carbon::parse($checkinString)->format('d.m.Y') . " bis " . Carbon::parse($checkoutString)->format('d.m.Y'),
                    'read' => false
                ]);

                return $this->successResponse('Sync successful.', [
                    'customer_id' => $customer->id,
                    'dog_id' => $dog->id,
                    'reservation_id' => $reservation->id,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('External sync failed: ' . $e->getMessage());
            return $this->errorResponse('Synchronisierung fehlgeschlagen: ' . $e->getMessage(), null, 500);
        }
    }

    public function cancelReservation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_remote_id' => 'required',
        ]);

        if ($validator->fails()) {
            Log::warning('External cancel validation failed.', $validator->errors()->toArray());
            return $this->errorResponse('Ungültige Stornierungsanfrage.', $validator->errors(), 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $remoteId = $request->input('reservation_remote_id');
                $reservation = Reservation::where('remote_pfotenstube_homepage_id', $remoteId)->first();

                if (!$reservation) {
                    Log::warning('Reservation not found for remote ID: ' . $remoteId);
                    return $this->errorResponse('Reservierung nicht gefunden.', null, 404);
                }

                // Check if it's already in room or checked out
                if ($reservation->status !== Reservation::STATUS_RESERVED) {
                    Log::warning('Attempted to cancel active/finished reservation. Status: ' . $reservation->status);
                    return $this->errorResponse('Reservierung kann nicht storniert werden, da sie bereits aktiv oder abgeschlossen ist.', null, 403);
                }

                $reservation->update(['status' => Reservation::STATUS_CANCELLED]);

                // Create Notification
                $dog = $reservation->dog;
                Notification::create([
                    'dog_id' => $dog->id,
                    'type' => 'reservation_cancelled',
                    'title' => 'Reservierung storniert',
                    'message' => "Reservierung für {$dog->name} wurde storniert (" . $reservation->checkin_date->format('d.m.Y') . " - " . $reservation->checkout_date->format('d.m.Y') . ")",
                    'read' => false
                ]);

                return $this->successResponse('Reservation cancelled successfully.');
            });
        } catch (\Exception $e) {
            Log::error('External cancel failed: ' . $e->getMessage());
            return $this->errorResponse('Stornierung fehlgeschlagen: ' . $e->getMessage(), null, 500);
        }
    }
}
