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

        // Calculate days based on configuration (inclusive or exclusive)
        // Same-day (29-29) always counts as 1 day regardless of mode
        // Inclusive: both checkin and checkout dates count (29-30 = 2 days, 29-31 = 3 days)
        // Exclusive: days between count, same-day is 1 (29-30 = 1 day, 29-31 = 2 days)
        $checkinStart = $checkinDate->copy()->startOfDay();
        $checkoutEnd = $checkoutDate->copy()->startOfDay();
        $daysDiff = $checkinStart->diffInDays($checkoutEnd);
        
        // Same-day checkin/checkout always counts as 1 day
        if ($daysDiff === 0) {
            $days = 1;
        } else {
            // Apply calculation mode from config
            $calculationMode = config('app.days_calculation_mode', 'inclusive');
            $days = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
        }

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

