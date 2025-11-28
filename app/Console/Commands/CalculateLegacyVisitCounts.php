<?php

namespace App\Console\Commands;

use App\Models\Dog;
use App\Models\Reservation;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateLegacyVisitCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visits:calculate-legacy 
                            {--dry-run : Show what would be updated without making changes}
                            {--chunk=500 : Number of dogs to process per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and populate visit/day counts for legacy records from existing reservations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Calculating legacy visit counts from reservations...');
        $this->newLine();

        $totalDogs = Dog::count();
        $processed = 0;
        $updated = 0;
        $created = 0;
        $skipped = 0;

        Dog::chunk($chunkSize, function ($dogs) use (&$processed, &$updated, &$created, &$skipped, $dryRun) {
            foreach ($dogs as $dog) {
                $processed++;

                // Get only checked-out reservations (status 2) for legacy calculation
                // Ongoing reservations (status 1) will be handled by the controller when they checkout
                $reservations = Reservation::where('dog_id', $dog->id)
                    ->where('status', 2) // Only fully checked out reservations
                    ->whereNotNull('checkin_date')
                    ->whereNotNull('checkout_date')
                    ->get();

                if ($reservations->isEmpty()) {
                    $skipped++;
                    continue;
                }

                // Calculate visits: count all checked-out reservations
                // This represents total number of completed stays
                $totalVisits = $reservations->count();

                // Calculate total days from all checked-out reservations
                // Days calculated as 1 day per night (not inclusive)
                // Same-day checkin/checkout counts as 1 day
                $totalDays = $reservations->sum(function ($reservation) {
                    $checkinDate = Carbon::parse($reservation->checkin_date)->startOfDay();
                    $checkoutDate = Carbon::parse($reservation->checkout_date)->startOfDay();

                    // Ensure we don't count future dates
                    if ($checkoutDate->isFuture()) {
                        $checkoutDate = Carbon::today()->startOfDay();
                    }

                    // Calculate days as 1 day per night
                    // Example: checkin 2025-07-17, checkout 2025-07-22
                    // Nights: 17-18, 18-19, 19-20, 20-21, 21-22 = 5 nights = 5 days
                    $days = $checkinDate->diffInDays($checkoutDate);
                    return max(1, $days); // At least 1 day (for same-day checkin/checkout)
                });

                // Get or create visit record
                $visit = Visit::where('dog_id', $dog->id)->first();

                if ($visit) {
                    // Check if it already has meaningful values (both > 0)
                    $existingVisits = (int)($visit->visits ?? 0);
                    $existingDays = (int)($visit->stay ?? 0);

                    // Only skip if BOTH visits and days are > 0 (meaningful data)
                    // If visits is 0, we should recalculate even if days > 0
                    if ($existingVisits > 0 && $existingDays > 0) {
                        // Has meaningful existing values - skip to preserve manual entries
                        $skipped++;
                        $this->line("  ⏭️  Dog ID {$dog->id} ({$dog->name}): Already has counts ({$existingVisits}/{$existingDays}) - Skipped");
                        continue;
                    }

                    // Update with calculated values from all reservations
                    // This represents the total counts from all historical data
                    if (!$dryRun) {
                        $visit->visits = $totalVisits;
                        $visit->stay = $totalDays;
                        $visit->save();
                    }
                    $updated++;
                    $this->line("  ✅ Dog ID {$dog->id} ({$dog->name}): Updated to {$totalVisits}/{$totalDays} (from {$existingVisits}/{$existingDays})");
                } else {
                    if (!$dryRun) {
                        Visit::create([
                            'dog_id' => $dog->id,
                            'visits' => $totalVisits,
                            'stay' => $totalDays,
                        ]);
                    }
                    $created++;
                    $this->line("  ✨ Dog ID {$dog->id} ({$dog->name}): Created with {$totalVisits}/{$totalDays}");
                }

                // Mark all checked-out reservations as counted (to prevent double-counting in future)
                // Ongoing reservations (status 1) will be marked when they checkout via controller
                if (!$dryRun) {
                    Reservation::where('dog_id', $dog->id)
                        ->where('status', 2) // Only mark checked-out reservations
                        ->update([
                            'visit_counted' => true,
                            'days_counted' => true,
                        ]);
                }
            }
        });

        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("  Total dogs processed: {$processed}");
        $this->line("  Visit records created: {$created}");
        $this->line("  Visit records updated: {$updated}");
        $this->line("  Skipped (no reservations or existing counts): {$skipped}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ All legacy records have been calculated and marked as counted.');
        }

        return Command::SUCCESS;
    }
}
