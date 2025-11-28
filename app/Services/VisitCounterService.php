<?php

namespace App\Services;

use App\Models\Dog;
use App\Models\Visit;
use Carbon\Carbon;

class VisitCounterService
{
    /**
     * Increment visit count for a dog
     * Called when a dog checks out (status becomes 2)
     * This adds to existing counts if the dog was added with initial values
     * Uses locking to prevent race conditions in concurrent checkouts
     */
    public function incrementVisit(int $dogId): void
    {
        // Use lockForUpdate to prevent race conditions when multiple checkouts happen simultaneously
        $visit = Visit::lockForUpdate()->firstOrCreate(
            ['dog_id' => $dogId],
            ['visits' => 0, 'stay' => 0]
        );

        $visit->increment('visits');
    }

    /**
     * Increment day count for a dog based on stay duration
     * Called when a dog checks out (status becomes 2)
     * 
     * @param int $dogId
     * @param Carbon $checkinDate
     * @param Carbon $checkoutDate
     * @throws \Exception If checkin date is null or checkout date is invalid
     */
    public function incrementDays(int $dogId, Carbon $checkinDate, Carbon $checkoutDate): void
    {
        // Validate checkin date is not null
        if (!$checkinDate) {
            throw new \Exception('Check-in-Datum darf nicht leer sein');
        }

        // Validate checkout date is not in the future
        if ($checkoutDate->isFuture()) {
            throw new \Exception('Check-out-Datum darf nicht in der Zukunft liegen');
        }

        // Validate checkout date is not before checkin date (normalize to start of day for comparison)
        // Same-day checkin/checkout is allowed (counts as 1 day)
        $checkinDay = $checkinDate->copy()->startOfDay();
        $checkoutDay = $checkoutDate->copy()->startOfDay();
        if ($checkoutDay->lt($checkinDay)) {
            throw new \Exception('Check-out-Datum darf nicht vor dem Check-in-Datum liegen');
        }

        // Use lockForUpdate to prevent race conditions when multiple checkouts happen simultaneously
        $visit = Visit::lockForUpdate()->firstOrCreate(
            ['dog_id' => $dogId],
            ['visits' => 0, 'stay' => 0]
        );

        // Calculate days as 1 day per night (not inclusive)
        // Example: checkin 2025-07-17, checkout 2025-07-22
        // Nights: 17-18, 18-19, 19-20, 20-21, 21-22 = 5 nights = 5 days
        // Same-day checkin/checkout counts as 1 day
        $checkinStart = $checkinDate->copy()->startOfDay();
        $checkoutEnd = $checkoutDate->copy()->startOfDay();
        $days = $checkinStart->diffInDays($checkoutEnd);
        $days = max(1, $days); // At least 1 day (for same-day checkin/checkout)

        $visit->increment('stay', $days);
    }

    /**
     * Get current visit and day counts for a dog
     */
    public function getCounts(int $dogId): array
    {
        $visit = Visit::where('dog_id', $dogId)->first();

        return [
            'visits' => $visit ? $visit->visits : 0,
            'days' => $visit ? $visit->stay : 0,
        ];
    }

    /**
     * Update initial counts (when editing dog)
     * This sets the base values from which counting continues
     */
    public function setInitialCounts(int $dogId, int $visits, int $days): void
    {
        Visit::updateOrCreate(
            ['dog_id' => $dogId],
            ['visits' => $visits, 'stay' => $days]
        );
    }
}

