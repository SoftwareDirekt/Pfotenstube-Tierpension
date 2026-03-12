<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HomepageSyncService
{
    protected string $baseUrl;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.pfotenstube.homepage_url');
        $this->webhookSecret = config('services.pfotenstube.webhook_secret');
    }

    /**
     * Update reservation status on the homepage.
     *
     * @param Reservation $reservation
     * @param string $status
     * @return bool
     */
    public function updateStatus(Reservation $reservation, string $status): bool
    {
        if (!$reservation->remote_pfotenstube_homepage_id) {
            return false;
        }

        if (!$this->baseUrl || !$this->webhookSecret) {
            Log::warning('Homepage status sync skipped: Missing credentials.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->webhookSecret,
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/local-api/reservation/status', [
                'remote_id' => $reservation->remote_pfotenstube_homepage_id,
                'status' => $status,
            ]);

            if ($response->successful()) {
                Log::info("Homepage status updated to {$status} for reservation " . $reservation->id);
                return true;
            } else {
                Log::error("Homepage status update failed for reservation " . $reservation->id, [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Homepage status sync exception: ' . $e->getMessage());
            return false;
        }
    }
}
