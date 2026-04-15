<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ApiJsonResponses;
use App\Models\Customer;
use App\Models\Dog;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Reservation;
use App\Models\Visit;
use App\Services\HelloCashService;
use App\Services\HomepageAnimalMediaImporter;
use App\Services\ReservationGroupLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * SyncController is responsible for syncing data between Tierpension and the Pfotenstube Homepage.
 * It is used to sync customers, dogs, reservations and other data between the Pfotenstube Homepage and Tierpension.
 */
class SyncController extends Controller
{
    use ApiJsonResponses;

    public function __construct(
        private readonly HelloCashService $helloCashService,
        private readonly HomepageAnimalMediaImporter $homepageAnimalMediaImporter
    ) {}

    public function syncReservation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer.remote_id' => 'required',
            'customer.name' => 'required|string',
            'customer.email' => 'required|email',
            'animal.remote_id' => 'required',
            'animal.name' => 'required|string',
            'animal.photo_url' => 'nullable|string',
            'animal.photo_base64' => 'nullable|string',
            'animal.photo_path_hint' => 'nullable|string|max:512',
            'animal.vaccine_pass_page1_url' => 'nullable|string',
            'animal.vaccine_pass_page1_base64' => 'nullable|string',
            'animal.vaccine_pass_page1_path_hint' => 'nullable|string|max:512',
            'animal.vaccine_pass_page2_url' => 'nullable|string',
            'animal.vaccine_pass_page2_base64' => 'nullable|string',
            'animal.vaccine_pass_page2_path_hint' => 'nullable|string|max:512',
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
                if (! $customer) {
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
                $dog = Dog::where('remote_pfotenstube_homepage_id', $animalData['remote_id'])->first();
                if (! $dog) {
                    $dog = Dog::where('customer_id', $customer->id)
                        ->where('name', $animalData['name'])
                        ->first();
                }

                if (! $dog) {
                    // Assign default plan
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
                        'picture' => 'no-user-picture.gif',
                        'neutered' => 0,
                        'chip_not_applicable' => false,
                    ]);
                    Visit::create([
                        'dog_id' => $dog->id,
                        'visits' => 0,
                        'stay' => 0,
                    ]);
                }

                $dog->remote_pfotenstube_homepage_id = $animalData['remote_id'];
                $dog->save();

                $this->applyHomepageAnimalToDog($dog->fresh(), $animalData);

                $checkinString = $reservationData['checkin_date'];
                $checkoutString = $reservationData['checkout_date'];
                $checkin = Carbon::parse($checkinString)->startOfDay()->addMinutes(5);
                $checkout = Carbon::parse($checkoutString)->endOfDay();

                $blockingStatuses = [
                    Reservation::STATUS_ACTIVE,
                    Reservation::STATUS_RESERVED,
                    Reservation::STATUS_PENDING_CONFIRMATION,
                ];

                $hasConflict = Reservation::where('dog_id', $dog->id)
                    ->whereIn('status', $blockingStatuses)
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
                    Log::warning('Reservation Date conflict for dog ID: '.$dog->id);

                    return $this->errorResponse('Terminkonflikt für diesen Hund. Der ausgewählte Zeitraum ist bereits belegt.', null, 422);
                }

                $plans = Plan::orderBy('id')->pluck('id')->values();
                $planId = $plans[0];

                $existingReservation = Reservation::where(
                    'remote_pfotenstube_homepage_id',
                    $reservationData['remote_id']
                )->first();

                $status = Reservation::STATUS_PENDING_CONFIRMATION;
                if ($existingReservation && in_array((int) $existingReservation->status, [
                    Reservation::STATUS_ACTIVE,
                    Reservation::STATUS_RESERVED,
                    Reservation::STATUS_CHECKED_OUT,
                ], true)) {
                    $status = (int) $existingReservation->status;
                }

                $reservation = Reservation::updateOrCreate(
                    ['remote_pfotenstube_homepage_id' => $reservationData['remote_id']],
                    [
                        'dog_id' => $dog->id,
                        'plan_id' => $planId,
                        'checkin_date' => $checkin,
                        'checkout_date' => $checkout,
                        'status' => $status,
                    ]
                );

                if ($reservation->wasRecentlyCreated) {
                    Notification::create([
                        'dog_id' => $dog->id,
                        'type' => 'new_reservation',
                        'title' => 'Neue Reservierung',
                        'message' => 'Online-Anfrage für '.$dog->name.' von '
                            .Carbon::parse($checkinString)->format('d.m.Y').' bis '
                            .Carbon::parse($checkoutString)->format('d.m.Y')
                            .' — unter „Pfotenstube-Anfragen“ prüfen und bestätigen oder ablehnen.',
                        'read' => false,
                    ]);
                }

                return $this->successResponse('Sync successful.', [
                    'customer_id' => $customer->id,
                    'dog_id' => $dog->id,
                    'reservation_id' => $reservation->id,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('External sync failed: '.$e->getMessage());

            return $this->errorResponse('Synchronisierung fehlgeschlagen: '.$e->getMessage(), null, 500);
        }
    }

    public function syncDog(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'animal.remote_id' => 'required',
            'animal.name' => 'required|string',
            'animal.breed' => 'nullable|string',
            'animal.gender' => 'nullable|string',
            'animal.birth_date' => 'nullable|string',
            'animal.chip_number' => 'nullable|string',
            'animal.chip_not_applicable' => 'nullable|boolean',
            'animal.castrated' => 'nullable|boolean',
            'animal.photo_url' => 'nullable|string',
            'animal.photo_base64' => 'nullable|string',
            'animal.photo_path_hint' => 'nullable|string|max:512',
            'animal.vaccine_pass_page1_url' => 'nullable|string',
            'animal.vaccine_pass_page1_base64' => 'nullable|string',
            'animal.vaccine_pass_page1_path_hint' => 'nullable|string|max:512',
            'animal.vaccine_pass_page2_url' => 'nullable|string',
            'animal.vaccine_pass_page2_base64' => 'nullable|string',
            'animal.vaccine_pass_page2_path_hint' => 'nullable|string|max:512',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validierungsfehler.', $validator->errors(), 422);
        }

        try {
            $animalData = $request->input('animal');
            $dog = Dog::where('remote_pfotenstube_homepage_id', $animalData['remote_id'])->first();
            if (! $dog) {
                return $this->errorResponse('Hund nicht gefunden.', null, 404);
            }

            $this->applyHomepageAnimalToDog($dog, $animalData);

            return $this->successResponse('Hund aktualisiert.');
        } catch (\Exception $e) {
            Log::error('External dog sync failed: '.$e->getMessage());

            return $this->errorResponse('Aktualisierung fehlgeschlagen.', null, 500);
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

                if (! $reservation) {
                    Log::warning('Reservation not found for remote ID: '.$remoteId);

                    return $this->errorResponse('Reservierung nicht gefunden.', null, 404);
                }

                $cancellable = in_array((int) $reservation->status, [
                    Reservation::STATUS_RESERVED,
                    Reservation::STATUS_PENDING_CONFIRMATION,
                ], true);

                if (! $cancellable) {
                    Log::warning('Attempted to cancel active/finished reservation. Status: '.$reservation->status);

                    return $this->errorResponse('Reservierung kann nicht storniert werden, da sie bereits aktiv oder abgeschlossen ist.', null, 403);
                }

                $groupId = $reservation->reservation_group_id;

                $reservation->update([
                    'status'               => Reservation::STATUS_CANCELLED,
                    'reservation_group_id' => null,
                ]);

                // Permanently remove payment records so they are fully gone from the DB.
                // Both models use SoftDeletes, so forceDelete() is required — delete() would
                // only set deleted_at and leave the rows behind.
                $reservation->load('reservationPayment.entries');
                if ($reservation->reservationPayment) {
                    $reservation->reservationPayment->entries()->withTrashed()->forceDelete();
                    $reservation->reservationPayment->forceDelete();
                }

                $reservation->additionalCosts()->delete();

                if ($groupId) {
                    app(ReservationGroupLifecycleService::class)->afterMemberRemoved((int) $groupId);
                }

                $dog = $reservation->dog;
                Notification::create([
                    'dog_id' => $dog->id,
                    'type' => 'reservation_cancelled',
                    'title' => 'Reservierung storniert',
                    'message' => 'Reservierung für '.$dog->name.' wurde storniert ('.$reservation->checkin_date->format('d.m.Y').' - '.$reservation->checkout_date->format('d.m.Y').')',
                    'read' => false,
                ]);

                return $this->successResponse('Reservation cancelled successfully.');
            });
        } catch (\Exception $e) {
            Log::error('External cancel failed: '.$e->getMessage());

            return $this->errorResponse('Stornierung fehlgeschlagen: '.$e->getMessage(), null, 500);
        }
    }

    private function applyHomepageAnimalToDog(Dog $dog, array $animalData): void
    {
        $chipNotApplicable = filter_var($animalData['chip_not_applicable'] ?? false, FILTER_VALIDATE_BOOL);
        $chipNumber = $chipNotApplicable ? null : ($animalData['chip_number'] ?? $dog->chip_number);
        $neutered = filter_var($animalData['castrated'] ?? ($dog->neutered ? true : false), FILTER_VALIDATE_BOOL) ? 1 : 0;

        $picture = $dog->picture ?: 'no-user-picture.gif';
        $importedPhoto = $this->homepageAnimalMediaImporter->importFromBase64(
            $animalData['photo_base64'] ?? null,
            $animalData['photo_path_hint'] ?? null,
            'profile'
        );
        if (! $importedPhoto && ! empty($animalData['photo_url'])) {
            $importedPhoto = $this->homepageAnimalMediaImporter->importFromUrl($animalData['photo_url'], 'profile');
        }
        if ($importedPhoto) {
            $picture = $importedPhoto;
        }

        $v1 = $dog->vaccine_pass_page1;
        $v2 = $dog->vaccine_pass_page2;
        $f1 = $this->homepageAnimalMediaImporter->importFromBase64(
            $animalData['vaccine_pass_page1_base64'] ?? null,
            $animalData['vaccine_pass_page1_path_hint'] ?? null,
            'vaccine'
        );
        if (! $f1 && ! empty($animalData['vaccine_pass_page1_url'])) {
            $f1 = $this->homepageAnimalMediaImporter->importFromUrl($animalData['vaccine_pass_page1_url'], 'vaccine');
        }
        if ($f1) {
            $v1 = $f1;
        }

        $f2 = $this->homepageAnimalMediaImporter->importFromBase64(
            $animalData['vaccine_pass_page2_base64'] ?? null,
            $animalData['vaccine_pass_page2_path_hint'] ?? null,
            'vaccine'
        );
        if (! $f2 && ! empty($animalData['vaccine_pass_page2_url'])) {
            $f2 = $this->homepageAnimalMediaImporter->importFromUrl($animalData['vaccine_pass_page2_url'], 'vaccine');
        }
        if ($f2) {
            $v2 = $f2;
        }

        $dog->update([
            'name' => $animalData['name'],
            'age' => $animalData['birth_date'] ?? $dog->age,
            'gender' => $animalData['gender'] ?? $dog->gender,
            'compatible_breed' => $animalData['breed'] ?? $dog->compatible_breed,
            'chip_number' => $chipNumber,
            'chip_not_applicable' => $chipNotApplicable,
            'neutered' => $neutered,
            'picture' => $picture,
            'vaccine_pass_page1' => $v1,
            'vaccine_pass_page2' => $v2,
        ]);
    }
}
