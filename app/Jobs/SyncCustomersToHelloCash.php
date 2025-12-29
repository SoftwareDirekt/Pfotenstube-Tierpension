<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\HelloCashService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCustomersToHelloCash implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Number of requests per minute for rate limiting
     *
     * @var int
     */
    public int $ratePerMinute;

    /**
     * Create a new job instance.
     */
    public function __construct(int $ratePerMinute = 10)
    {
        $this->ratePerMinute = $ratePerMinute;
    }

    /**
     * Execute the job.
     */
    public function handle(HelloCashService $service): void
    {
        // Get all customers without HelloCash ID
        $customers = Customer::whereNull('hellocash_customer_id')->get();

        if ($customers->isEmpty()) {
            return;
        }

        $total = $customers->count();
        $delaySeconds = 60 / $this->ratePerMinute;
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $failedCustomers = [];

        Log::info("Starting HelloCash sync for {$total} customers", [
            'rate_per_minute' => $this->ratePerMinute,
        ]);

        foreach ($customers as $index => $customer) {
            try {
                // Refresh to get latest data (in case customer was synced by another process)
                $customer->refresh();
                
                // Check if customer still exists (might have been deleted)
                if (!$customer->exists) {
                    $skippedCount++;
                    continue;
                }
                
                // Skip if already synced (double-check after refresh)
                if (!empty($customer->hellocash_customer_id)) {
                    $skippedCount++;
                    continue;
                }

                // Rate limiting: sleep between requests (except for first request)
                if ($index > 0) {
                    sleep($delaySeconds);
                }

                // Attempt to create user in HelloCash
                $result = $service->createUser($customer);

                if ($result['success'] && !empty($result['user_id'])) {
                    // Set HelloCash ID
                    $customer->hellocash_customer_id = (int)$result['user_id'];
                    $customer->save();
                    $successCount++;
                } else {
                    $failedCount++;
                    $error = $result['error'] ?? 'HelloCash API returned success but user_id is missing in response';
                    $failedCustomers[] = [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'error' => $error,
                    ];
                    Log::warning('Customer sync failed in background job', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'error' => $error,
                    ]);
                }
            } catch (\Exception $e) {
                // Handle unexpected exceptions for individual customers
                $failedCount++;
                $failedCustomers[] = [
                    'id' => $customer->id ?? null,
                    'name' => $customer->name ?? 'Unknown',
                    'error' => 'Unexpected exception: ' . $e->getMessage(),
                ];
                Log::error('Exception while syncing customer in background job', [
                    'customer_id' => $customer->id ?? null,
                    'customer_name' => $customer->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('HelloCash customer sync completed', [
            'total' => $total,
            'success' => $successCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
        ]);

        if (!empty($failedCustomers)) {
            Log::warning('Some customers failed to sync and will be retried on next run', [
                'failed_count' => $failedCount,
                'failed_customers' => $failedCustomers,
            ]);
        }
    }

    /**
     * Handle a job failure (only called if entire job fails, not individual customer failures).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('HelloCash sync job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
