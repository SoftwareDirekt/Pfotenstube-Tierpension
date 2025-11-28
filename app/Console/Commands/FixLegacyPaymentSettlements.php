<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Customer;
use App\Services\CustomerBalanceService;
use Illuminate\Support\Facades\DB;

class FixLegacyPaymentSettlements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:fix-legacy-settlements 
                            {--dry-run : Preview changes without applying them}
                            {--chunk=100 : Number of payments to process at a time}
                            {--customer= : Process only specific customer ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix legacy payment records to use debt settlement logic. Recalculates remaining_amount, advance_payment, and creates settlement records for audit trail. Processes payments chronologically per customer.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $customerId = $this->option('customer');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Fixing legacy payment settlements with debt consideration...');
        $this->newLine();

        $balanceService = new CustomerBalanceService();
        $processed = 0;
        $updated = 0;
        $errors = 0;

        // Group payments by customer and process chronologically
        $query = Payment::with('reservation.dog')
            ->whereHas('reservation.dog', function($q) use ($customerId) {
                if ($customerId) {
                    $q->where('customer_id', $customerId);
                }
            })
            ->orderBy('created_at', 'asc');

        $totalPayments = $query->count();
        
        if ($totalPayments === 0) {
            $this->warn('No payments found to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalPayments} payments to process.");
        $this->newLine();

        // Group by customer to process chronologically per customer
        $paymentsByCustomer = $query->get()->groupBy(function($payment) {
            return $payment->reservation->dog->customer_id ?? 'unknown';
        });

        foreach ($paymentsByCustomer as $customerIdKey => $payments) {
            if ($customerIdKey === 'unknown') {
                $this->warn('Skipping payments without valid customer ID');
                continue;
            }

            $customer = Customer::find($customerIdKey);
            if (!$customer) {
                $this->warn("Customer ID {$customerIdKey} not found, skipping payments");
                continue;
            }

            // Reset customer balance to 0 for recalculation
            $originalBalance = $customer->balance ?? 0;
            
            $this->line("Processing customer: {$customer->name} (ID: {$customerIdKey})");
            $customerUpdated = 0;
            
            // Start transaction for this customer
            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    $customer->balance = 0;
                    $customer->save();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Error resetting balance for customer ID {$customerIdKey}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Process payments chronologically for this customer
            foreach ($payments as $payment) {
                try {
                    $reservation = $payment->reservation;
                    if (!$reservation || !$reservation->dog) {
                        $this->warn("Payment ID {$payment->id} has no valid reservation/dog, skipping");
                        continue;
                    }

                    $invoiceTotal = (float) ($payment->cost ?? 0);
                    $receivedAmount = (float) ($payment->received_amount ?? 0);
                    $walletAmount = (float) ($payment->wallet_amount ?? 0);

                    // Calculate settlement with debt consideration
                    // Pass payment ID to create settlement records for audit trail
                    $settlement = $balanceService->settlePaymentWithDebtConsideration(
                        $customerIdKey,
                        $invoiceTotal,
                        $receivedAmount,
                        $walletAmount,
                        $payment->id, // Pass payment ID to create settlement records
                        true // Create settlement records
                    );

                    $newRemaining = $settlement['remaining_amount'];
                    $newAdvance = $settlement['advance_payment'];
                    $oldRemaining = (float) ($payment->remaining_amount ?? 0);
                    $oldAdvance = (float) ($payment->advance_payment ?? 0);

                    // Check if values changed
                    $hasChanges = abs($oldRemaining - $newRemaining) > 0.01 || 
                                  abs($oldAdvance - $newAdvance) > 0.01;

                    if ($hasChanges) {
                        if ($dryRun) {
                            $this->line("  Payment ID {$payment->id}:");
                            $this->line("    Remaining: {$oldRemaining}€ → {$newRemaining}€");
                            $this->line("    Advance: {$oldAdvance}€ → {$newAdvance}€");
                            $this->line("    Debt Settled: {$settlement['debt_settled']}€");
                            $this->line("    Balance Change: {$settlement['balance_change']}€");
                            if (!empty($settlement['old_payments_settled'])) {
                                $this->line("    Settled " . count($settlement['old_payments_settled']) . " old payment(s)");
                            }
                        } else {
                            try {
                                // Update payment record
                                $payment->remaining_amount = $newRemaining < 0.01 ? 0 : $newRemaining;
                                $payment->advance_payment = $newAdvance < 0.01 ? 0 : $newAdvance;
                                
                                // Update status if fully settled
                                if ($newRemaining < 0.01 && in_array($payment->status, [0, 2])) {
                                    $payment->status = 1; // Bezahlt
                                }
                                
                                $payment->save();

                                // Update customer balance
                                $balanceService->updateBalanceByChange($customerIdKey, $settlement['balance_change']);
                                $customerUpdated++;
                            } catch (\Exception $e) {
                                $this->error("    Error updating payment ID {$payment->id}: " . $e->getMessage());
                                throw $e; // Re-throw to trigger outer catch
                            }
                        }
                        $updated++;
                    }

                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error processing payment ID {$payment->id}: " . $e->getMessage());
                    if (!$dryRun) {
                        DB::rollBack();
                        $this->error("  Transaction rolled back for customer ID {$customerIdKey}");
                        // Restore original balance
                        $customer->balance = $originalBalance;
                        $customer->save();
                        break; // Skip remaining payments for this customer
                    }
                }
            }

            // Commit transaction for this customer
            if (!$dryRun && $customerUpdated > 0) {
                try {
                    DB::commit();
                    $this->line("  ✅ Updated {$customerUpdated} payment(s) for this customer");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  Error committing transaction: " . $e->getMessage());
                    // Restore original balance
                    $customer->balance = $originalBalance;
                    $customer->save();
                }
            }

            // Show final balance for this customer
            $finalBalance = $balanceService->getBalance($customerIdKey);
            if ($dryRun) {
                $this->line("  Final balance: {$finalBalance}€ (was {$originalBalance}€)");
            } else {
                $this->line("  Final balance: {$finalBalance}€");
            }
            $this->newLine();
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->line("Total payments processed: {$processed}");
        $this->line("Payments updated: {$updated}");
        if ($errors > 0) {
            $this->error("Errors: {$errors}");
        }

        if ($dryRun) {
            $this->warn('⚠️  This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->info('✅ Legacy payment settlements fixed successfully!');
            $this->info('💡 Run "php artisan customers:reconcile-balances" to verify balances are correct.');
        }

        return Command::SUCCESS;
    }
}
