<?php

namespace App\Services;

use App\Models\Preference;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\ConnectionException;
use Exception;

class HelloCashService
{
    protected $baseUrl;
    protected $apiKey;
    protected $signatureMandatory;
    protected $testMode;
    protected $syncEnabled;

    public function __construct()
    {
        $this->baseUrl            = config('services.hellocash.base_url', 'https://api.hellocash.business/api/v1');
        $this->apiKey             = config('services.hellocash.api_key', '');
        $this->signatureMandatory = filter_var(config('services.hellocash.signature_mandatory', false), FILTER_VALIDATE_BOOLEAN);
        $this->testMode           = filter_var(config('services.hellocash.test_mode', true), FILTER_VALIDATE_BOOLEAN);
        $this->syncEnabled        = filter_var(config('services.hellocash.sync_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    /** Returns true when outbound HelloCash calls are globally disabled via HELLOCASH_SYNC_ENABLED=false. */
    public function isSyncDisabled(): bool
    {
        return ! $this->syncEnabled;
    }

    public function createCashPaymentInvoice(array $data): array
    {
        if (! $this->syncEnabled) {
            Log::info('HelloCash sync disabled (HELLOCASH_SYNC_ENABLED=false). Skipping createCashPaymentInvoice.');
            return ['success' => false, 'skipped' => true, 'message' => 'HelloCash sync is disabled.'];
        }

        try {
            if (empty($this->apiKey)) {
                throw new Exception('HelloCash API key is not configured');
            }

            $payload = $this->buildCashPaymentPayload($data);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($jsonPayload === false) {
                throw new Exception('Failed to encode JSON payload: ' . json_last_error_msg());
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->withBody($jsonPayload, 'application/json')
              ->timeout(10)
              ->post($this->baseUrl . '/invoices');

            if ($response->successful()) {
                $responseData = $response->json();
                $invoiceId = $responseData['invoice_id'] ?? null;
                $invoicePdf = $responseData['pdf_base64_encoded'] ?? null;

                if (empty($invoicePdf) && $invoiceId) {
                    $pdfResult = $this->getInvoicePdf($invoiceId);
                    if ($pdfResult['success']) {
                        $invoicePdf = $pdfResult['pdf_base64'];
                    }
                }

                $savedInvoice = null;
                if ($invoiceId && $invoicePdf) {
                    $savedInvoice = $this->saveCashierInvoice([
                        'hellocash_invoice_id' => $invoiceId,
                        'invoice_pdf_base64' => $invoicePdf,
                        'reservation_id' => $data['reservation_id'] ?? null,
                        'customer_id' => $data['customer_id'] ?? null,
                    ]);
                }

                return [
                    'success' => true,
                    'invoice' => $responseData,
                    'invoice_id' => $invoiceId,
                    'invoice_pdf_base64' => $invoicePdf,
                    'saved_invoice_id' => $savedInvoice?->id,
                ];
            }

            $jsonResponse = $response->json();
            $apiError = is_array($jsonResponse)
                ? ($jsonResponse['message'] ?? $jsonResponse['error'] ?? 'Unknown API error')
                : ($response->status() === 403 ? 'Request blocked by firewall' : 'Unknown API error');

            Log::error('Failed to create cash payment invoice in HelloCash', [
                'http_status' => $response->status(),
                'error' => $apiError,
            ]);

            return [
                'success' => false,
                'error' => 'Fehler beim Erstellen der Registrierkassen-Rechnung. Bitte versuchen Sie es erneut.',
                'status_code' => $response->status(),
            ];
        } catch (ConnectionException $e) {
            $logMessage = 'HelloCash API connection error: ' . $e->getMessage();
            Log::error($logMessage);
            return ['success' => false, 'error' => 'Registrierkasse-Timeout oder Verbindungsfehler. Bitte versuchen Sie es erneut.'];
        } catch (Exception $e) {
            $logMessage = 'Exception while creating cash payment invoice in HelloCash: ' . $e->getMessage();
            Log::error($logMessage);
            return ['success' => false, 'error' => 'Fehler beim Erstellen der Rechnung. Bitte versuchen Sie es erneut.'];
        }
    }

    private function buildCashPaymentPayload(array $data): array
    {
        $paymentMethod = $data['payment_method'] ?? 'Bar';
        $items = $data['items'] ?? [];

        $payload = [
            'invoice_testMode' => $this->testMode,
            'invoice_paymentMethod' => $paymentMethod,
            'invoice_type' => 'pdf',
            'locale' => 'de_AT',
            'signature_mandatory' => $this->signatureMandatory,
            'invoice_text' => 'Vielen Dank fuer Ihren Besuch!',
            'items' => $items,
        ];

        $hellocashCustomerId = $data['hellocash_customer_id'] ?? null;
        if (!empty($hellocashCustomerId)) {
            $payload['invoice_user_id'] = $hellocashCustomerId;
        }

        return $payload;
    }

    public function getInvoicePdf(int $invoiceId, string $locale = 'de_AT', bool $cancellation = false): array
    {
        if (! $this->syncEnabled) {
            Log::info('HelloCash sync disabled (HELLOCASH_SYNC_ENABLED=false). Skipping getInvoicePdf.');
            return ['success' => false, 'skipped' => true, 'message' => 'HelloCash sync is disabled.'];
        }

        try {
            if (empty($this->apiKey)) {
                throw new Exception('HelloCash API key is not configured');
            }

            $pdfUrl = $this->baseUrl . '/invoices/' . $invoiceId . '/pdf';
            $pdfUrl .= '?cancellation=' . ($cancellation ? 'true' : 'false');
            $pdfUrl .= '&locale=' . $locale;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->get($pdfUrl);

            if ($response->successful()) {
                $jsonResponse = $response->json();
                $pdfBase64 = null;
                
                if (is_array($jsonResponse)) {
                    $pdfBase64 = $jsonResponse['pdf_base64_encoded'] 
                        ?? $jsonResponse['pdf'] 
                        ?? $jsonResponse['base64'] 
                        ?? null;
                } else {
                    $pdfBase64 = trim($response->body());
                }
                
                if (empty($pdfBase64) || base64_decode($pdfBase64, true) === false) {
                    $errorMessage = 'Invalid PDF data received from HelloCash API';
                    Log::error($errorMessage, ['invoice_id' => $invoiceId]);
                    return ['success' => false, 'error' => $errorMessage];
                }

                return ['success' => true, 'pdf_base64' => $pdfBase64];
            } else {
                $jsonResponse = $response->json();
                $apiError = is_array($jsonResponse) 
                    ? ($jsonResponse['error'] ?? $jsonResponse['message'] ?? 'Unknown API error')
                    : 'Unknown API error';
                
                Log::error('Failed to retrieve invoice PDF from HelloCash', [
                    'invoice_id' => $invoiceId,
                    'http_status' => $response->status(),
                    'error' => $apiError,
                ]);

                return ['success' => false, 'error' => 'Fehler beim Abrufen der Rechnung. Bitte versuchen Sie es erneut.', 'status_code' => $response->status()];
            }
        } catch (ConnectionException $e) {
            $logMessage = 'HelloCash API connection error: ' . $e->getMessage();
            Log::error($logMessage, ['invoice_id' => $invoiceId]);
            return ['success' => false, 'error' => 'Registrierkasse-Timeout oder Verbindungsfehler. Bitte versuchen Sie es erneut.'];
        } catch (Exception $e) {
            $logMessage = 'Exception while retrieving invoice PDF from HelloCash: ' . $e->getMessage();
            Log::error($logMessage, ['invoice_id' => $invoiceId]);
            return ['success' => false, 'error' => 'Fehler beim Abrufen der Rechnung. Bitte versuchen Sie es erneut.'];
        }
    }

    private function saveCashierInvoice(array $data): ?Invoice
    {
        try {
            $pdfContent = base64_decode($data['invoice_pdf_base64']);
            if ($pdfContent === false) {
                Log::error('Failed to decode cashier invoice PDF from base64', [
                    'hellocash_invoice_id' => $data['hellocash_invoice_id'] ?? null,
                ]);
                return null;
            }

            $invoiceId = $data['hellocash_invoice_id'] ?? null;
            if (!$invoiceId) {
                return null;
            }

            $invoiceNumber = 'HC-' . $invoiceId;
            $existing = Invoice::where('invoice_number', $invoiceNumber)->first();

            $invoiceDate = now();
            $year = $invoiceDate->format('Y');
            $month = $invoiceDate->format('m');
            $filename = 'invoice_hc_' . $invoiceId . '.pdf';
            $filePath = "invoices/{$year}/{$month}/{$filename}";

            Storage::disk('local')->put($filePath, $pdfContent);

            if ($existing) {
                $existing->update([
                    'file_path' => $filePath,
                ]);
                return $existing;
            }

            return Invoice::create([
                'invoice_number' => $invoiceNumber,
                'hellocash_invoice_id' => $invoiceId,
                'reservation_id' => $data['reservation_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'type' => 'hellocash',
                'file_path' => $filePath,
                'status' => 'paid',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to save cashier invoice', [
                'error' => $e->getMessage(),
                'hellocash_invoice_id' => $data['hellocash_invoice_id'] ?? null,
            ]);
            return null;
        }
    }

    public function createUser(Customer $customer): array
    {
        if (! $this->syncEnabled) {
            Log::info('HelloCash sync disabled (HELLOCASH_SYNC_ENABLED=false). Skipping createUser.');
            return ['success' => false, 'skipped' => true, 'message' => 'HelloCash sync is disabled.'];
        }

        try {
            if (empty($this->apiKey)) {
                throw new Exception('HelloCash API key is not configured');
            }

            $payload = $this->buildUserPayload($customer);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            if ($jsonPayload === false) {
                throw new Exception('Failed to encode JSON payload: ' . json_last_error_msg());
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->withBody($jsonPayload, 'application/json')
              ->timeout(10)
              ->post($this->baseUrl . '/users');

            if ($response->successful()) {
                $responseData = $response->json();
                $userId = $responseData['user_id'] ?? null;
                
                if ($userId === null) {
                    $logMessage = 'HelloCash API returned success but user_id is missing in response. This may indicate an API response format change.';
                    Log::error($logMessage, [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                    ]);
                    return ['success' => false, 'error' => 'Fehler bei der Registrierkasse-Synchronisation. Bitte versuchen Sie es erneut.'];
                }

                return [
                    'success' => true,
                    'user_id' => $userId,
                    'data' => $responseData,
                ];
            } else {
                $jsonResponse = $response->json();
                $apiError = is_array($jsonResponse) 
                    ? ($jsonResponse['message'] ?? $jsonResponse['error'] ?? 'Unknown API error')
                    : ($response->status() === 403 ? 'Request blocked by firewall' : 'Unknown API error');
                
                Log::error('Failed to create customer in HelloCash', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'http_status' => $response->status(),
                    'error' => $apiError,
                ]);

                return [
                    'success' => false,
                    'error' => 'Fehler beim Erstellen des Kunden in der Registrierkasse. Bitte versuchen Sie es erneut.',
                    'status_code' => $response->status(),
                ];
            }
        } catch (ConnectionException $e) {
            $logMessage = 'HelloCash API connection error: ' . $e->getMessage();
            Log::error($logMessage, [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);
            return ['success' => false, 'error' => 'Registrierkasse-Timeout oder Verbindungsfehler. Bitte versuchen Sie es erneut.'];
        } catch (Exception $e) {
            $logMessage = 'Exception while creating customer in HelloCash: ' . $e->getMessage();
            Log::error($logMessage, [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);
            return ['success' => false, 'error' => 'Fehler beim Erstellen des Kunden in der Registrierkasse. Bitte versuchen Sie es erneut.'];
        }
    }

    public function updateUser(int $hellocashCustomerId, Customer $customer): array
    {
        if (! $this->syncEnabled) {
            Log::info('HelloCash sync disabled (HELLOCASH_SYNC_ENABLED=false). Skipping updateUser.');
            return ['success' => false, 'skipped' => true, 'message' => 'HelloCash sync is disabled.'];
        }

        try {
            if (empty($this->apiKey)) {
                throw new Exception('HelloCash API key is not configured');
            }

            $payload = $this->buildUserPayload($customer);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            if ($jsonPayload === false) {
                throw new Exception('Failed to encode JSON payload: ' . json_last_error_msg());
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->withBody($jsonPayload, 'application/json')
              ->timeout(10)
              ->post($this->baseUrl . '/users/' . $hellocashCustomerId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                $jsonResponse = $response->json();
                $apiError = is_array($jsonResponse) 
                    ? ($jsonResponse['message'] ?? $jsonResponse['error'] ?? 'Unknown API error')
                    : ($response->status() === 403 ? 'Request blocked by firewall' : 'Unknown API error');
                
                Log::error('Failed to update customer in HelloCash', [
                    'customer_id' => $customer->id,
                    'hellocash_customer_id' => $hellocashCustomerId,
                    'http_status' => $response->status(),
                    'error' => $apiError,
                ]);

                return [
                    'success' => false,
                    'error' => 'Fehler beim Aktualisieren des Kunden in der Registrierkasse. Bitte versuchen Sie es erneut.',
                    'status_code' => $response->status(),
                ];
            }
        } catch (ConnectionException $e) {
            $logMessage = 'HelloCash API connection error: ' . $e->getMessage();
            Log::error($logMessage, [
                'customer_id' => $customer->id,
                'hellocash_customer_id' => $hellocashCustomerId,
            ]);
            return ['success' => false, 'error' => 'Registrierkasse-Timeout oder Verbindungsfehler. Bitte versuchen Sie es erneut.'];
        } catch (Exception $e) {
            $logMessage = 'Exception while updating customer in HelloCash: ' . $e->getMessage();
            Log::error($logMessage, [
                'customer_id' => $customer->id,
                'hellocash_customer_id' => $hellocashCustomerId,
            ]);
            return ['success' => false, 'error' => 'Fehler beim Aktualisieren des Kunden in der Registrierkasse. Bitte versuchen Sie es erneut.'];
        }
    }

    private function buildUserPayload(Customer $customer): array
    {
        // Split name into first name and last name
        $name = trim($customer->name ?? '');
        $firstName = null;
        $lastName = null;
        
        if (!empty($name)) {
            $nameParts = explode(' ', $name, 2);
            if (count($nameParts) === 2) {
                // Multiple words: first word = firstname, rest = surname
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];
            } else {
                // Single word: use as firstname, surname = "-" (safe string for API)
                $firstName = $nameParts[0];
                $lastName = '-';
            }
        } else {
            // Empty name: use safe defaults
            $lastName = '-';
            $firstName = '-';
        }

        // Determine company field based on type
        $company = 'Stammkunde'; 
        if ($customer->type === 'Organisation') {
            $company = !empty($customer->profession) ? $customer->profession : 'Organisation';
        } elseif ($customer->type === 'Stammkunde') {
            $company = 'Stammkunde';
        }

        // Build payload
        $payload = [
            // Required fields
            'user_surname' => $lastName,
            'user_company' => $company,
            'user_country_code' => 'AT',
            // Optional fields (can be sent null)
            'user_salutation' => $customer->title ?? null,
            'user_firstname' => $firstName,
            'user_email' => $customer->email ?? null,
            'user_phoneNumber' => $customer->phone ?? null,
            'user_postalCode' => $customer->zipcode ?? null,
            'user_city' => $customer->city ?? null,
            'user_street' => $customer->street ?? null,
        ];

        // Remove null values from optional fields only (required fields must stay present)
        return array_filter($payload, function ($value, $key) {
            // Always keep required fields
            if (in_array($key, ['user_surname', 'user_company', 'user_country_code'])) {
                return true;
            }
            // Remove null optional fields
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Syncs a customer to HelloCash.
     * Strategy:
     * 1) If local hellocash_customer_id exists -> update remote.
     * 2) Else try create.
     * 3) If create fails (e.g. duplicate), try to find existing remote user by email and link/update.
     */
    public function syncCustomer(Customer $customer): array
    {
        if (! $this->syncEnabled) {
            Log::info('HelloCash sync disabled (HELLOCASH_SYNC_ENABLED=false). Skipping syncCustomer.');
            return ['success' => false, 'skipped' => true, 'message' => 'HelloCash sync is disabled.'];
        }

        if (!empty($customer->hellocash_customer_id)) {
            $updateResult = $this->updateUser((int) $customer->hellocash_customer_id, $customer);
            if ($updateResult['success']) {
                return [
                    'success' => true,
                    'user_id' => (int) $customer->hellocash_customer_id,
                    'source' => 'updated_existing_link',
                    'data' => $updateResult['data'] ?? null,
                ];
            }
        }

        $createResult = $this->createUser($customer);
        if ($createResult['success'] && !empty($createResult['user_id'])) {
            return [
                'success' => true,
                'user_id' => (int) $createResult['user_id'],
                'source' => 'created',
                'data' => $createResult['data'] ?? null,
            ];
        }

        $existingUserId = $this->findUserIdByEmail($customer->email);
        if ($existingUserId) {
            $updateResult = $this->updateUser($existingUserId, $customer);
            if ($updateResult['success']) {
                return [
                    'success' => true,
                    'user_id' => (int) $existingUserId,
                    'source' => 'linked_existing_by_email',
                    'data' => $updateResult['data'] ?? null,
                ];
            }
        }

        return [
            'success' => false,
            'error' => $createResult['error'] ?? 'Fehler bei der Registrierkasse-Synchronisation. Bitte versuchen Sie es erneut.',
            'status_code' => $createResult['status_code'] ?? null,
        ];
    }

    private function findUserIdByEmail(?string $email): ?int
    {
        if (empty($email) || empty($this->apiKey)) {
            return null;
        }

        $queryVariants = [
            ['email' => $email],
            ['user_email' => $email],
            ['search' => $email],
            ['q' => $email],
        ];

        foreach ($queryVariants as $query) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->timeout(10)->get($this->baseUrl . '/users', $query);

                if (!$response->successful()) {
                    continue;
                }

                $json = $response->json();
                $userId = $this->extractUserIdFromUsersResponse($json, $email);
                if ($userId !== null) {
                    return $userId;
                }
            } catch (\Throwable $e) {
                Log::warning('HelloCash user lookup failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    private function extractUserIdFromUsersResponse(mixed $json, string $email): ?int
    {
        if (!is_array($json)) {
            return null;
        }

        $candidateCollections = [];

        if (isset($json['users']) && is_array($json['users'])) {
            $candidateCollections[] = $json['users'];
        }
        if (isset($json['data']) && is_array($json['data'])) {
            $candidateCollections[] = $json['data'];
        }
        if (array_is_list($json)) {
            $candidateCollections[] = $json;
        }

        foreach ($candidateCollections as $collection) {
            foreach ($collection as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $rowEmail = $row['user_email'] ?? $row['email'] ?? null;
                $rowUserId = $row['user_id'] ?? $row['id'] ?? null;
                if ($rowUserId === null) {
                    continue;
                }

                if ($rowEmail !== null && strcasecmp((string) $rowEmail, $email) !== 0) {
                    continue;
                }

                return (int) $rowUserId;
            }
        }

        return null;
    }
}
