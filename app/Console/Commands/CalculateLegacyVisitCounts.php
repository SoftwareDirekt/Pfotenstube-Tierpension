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
                            {--force : Force recalculation of all records, even if they already have counts}
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
        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($force) {
            $this->info('⚡ FORCE MODE - All records will be recalculated');
            $this->newLine();
        }

        $this->info('Calculating legacy visit counts from reservations...');
        $this->newLine();

        $totalDogs = Dog::count();
        $processed = 0;
        $updated = 0;
        $created = 0;
        $skipped = 0;

        Dog::chunk($chunkSize, function ($dogs) use (&$processed, &$updated, &$created, &$skipped, $dryRun, $force) {
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

                    // Calculate days based on configuration (inclusive or exclusive)
                    // Same-day (29-29) always counts as 1 day regardless of mode
                    // Inclusive: both checkin and checkout dates count (29-30 = 2 days, 29-31 = 3 days)
                    // Exclusive: days between count, same-day is 1 (29-30 = 1 day, 29-31 = 2 days)
                    $daysDiff = $checkinDate->diffInDays($checkoutDate);
                    
                    // Same-day checkin/checkout always counts as 1 day
                    if ($daysDiff === 0) {
                        return 1;
                    }
                    
                    $calculationMode = config('app.days_calculation_mode', 'inclusive');
                    $days = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                    return $days;
                });

                // Get or create visit record
                $visit = Visit::where('dog_id', $dog->id)->first();

                if ($visit) {
                    // Check if it already has meaningful values (both > 0)
                    $existingVisits = (int)($visit->visits ?? 0);
                    $existingDays = (int)($visit->stay ?? 0);

                    // Skip if force mode is not enabled and record has meaningful values
                    if (!$force && $existingVisits > 0 && $existingDays > 0) {
                        // Has meaningful existing values - skip to preserve manual entries
                        $skipped++;
                        $this->line("  ⏭️  Dog ID {$dog->id} ({$dog->name}): Already has counts ({$existingVisits}/{$existingDays}) - Skipped (use --force to recalculate)");
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
