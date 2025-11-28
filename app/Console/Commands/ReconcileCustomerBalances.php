<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Services\CustomerBalanceService;

class ReconcileCustomerBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:reconcile-balances 
                            {--dry-run : Preview changes without applying them}
                            {--chunk=100 : Number of customers to process at a time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile customer balances from payments table. Recalculates balance based on all payments, settlements, and wallet usage.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Reconciling customer balances from payments...');

        $balanceService = new CustomerBalanceService();
        $totalCustomers = Customer::count();
        $processed = 0;
        $updated = 0;
        $errors = 0;

        $this->withProgressBar(Customer::lazy($chunkSize), function ($customer) use ($balanceService, $dryRun, &$processed, &$updated, &$errors) {
            try {
                $oldBalance = (float) ($customer->balance ?? 0);
                $newBalance = $balanceService->reconcileBalance($customer->id);

                if (abs($oldBalance - $newBalance) > 0.01) {
                    if (!$dryRun) {
                        // Balance was already updated by reconcileBalance
                        $updated++;
                    } else {
                        $updated++;
                        $this->newLine();
                        $this->line("Customer ID {$customer->id} ({$customer->name}): {$oldBalance}€ → {$newBalance}€");
                    }
                }

                $processed++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing customer ID {$customer->id}: " . $e->getMessage());
            }
        });

        $this->newLine(2);
        $this->info("📊 Summary:");
        $this->line("Total customers processed: {$processed}");
        $this->line("Balances updated: {$updated}");
        if ($errors > 0) {
            $this->error("Errors: {$errors}");
        }

        if ($dryRun) {
            $this->warn('⚠️  This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->info('✅ Reconciliation completed successfully!');
        }

        return Command::SUCCESS;
    }
}
