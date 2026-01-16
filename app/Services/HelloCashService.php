<?php

namespace App\Services;

use App\Models\Preference;
use App\Models\HelloCashInvoice;
use App\Models\Customer;
use App\Models\Payment;
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

    public function __construct()
    {
        $this->baseUrl = config('services.hellocash.base_url', 'https://api.hellocash.business/api/v1');
        $this->apiKey = config('services.hellocash.api_key', '');
        $this->signatureMandatory = filter_var(config('services.hellocash.signature_mandatory', false), FILTER_VALIDATE_BOOLEAN);
        $this->testMode = filter_var(config('services.hellocash.test_mode', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function createInvoice(array $data): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new Exception('HelloCash API key is not configured');
            }

            $payload = $this->buildInvoicePayload($data);
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
                    $savedInvoice = $this->saveInvoice([
                        'hellocash_invoice_id' => $invoiceId,
                        'invoice_pdf_base64' => $invoicePdf,
                        'reservation_id' => $data['reservation_id'] ?? null,
                        'payment_id' => $data['payment_id'] ?? null,
                    ]);
                }
                
                return [
                    'success' => true,
                    'invoice' => $responseData,
                    'invoice_id' => $invoiceId,
                    'invoice_pdf_base64' => $invoicePdf,
                    'saved_invoice_id' => $savedInvoice?->id,
                ];
            } else {
                $jsonResponse = $response->json();
                $apiError = is_array($jsonResponse) 
                    ? ($jsonResponse['message'] ?? $jsonResponse['error'] ?? 'Unknown API error')
                    : ($response->status() === 403 ? 'Request blocked by firewall' : 'Unknown API error');
                
                Log::error('Failed to create invoice in HelloCash', [
                    'http_status' => $response->status(),
                    'error' => $apiError,
                ]);

                return [
                    'success' => false,
                    'error' => 'Fehler beim Erstellen der Rechnung in der Registrierkasse. Bitte versuchen Sie es erneut.',
                    'status_code' => $response->status(),
                ];
            }
        } catch (ConnectionException $e) {
            $logMessage = 'HelloCash API connection error: ' . $e->getMessage();
            Log::error($logMessage);
            return ['success' => false, 'error' => 'Registrierkasse-Timeout oder Verbindungsfehler. Bitte versuchen Sie es erneut.'];
        } catch (Exception $e) {
            $logMessage = 'Exception while creating invoice in HelloCash: ' . $e->getMessage();
            Log::error($logMessage);
            return ['success' => false, 'error' => 'Fehler beim Erstellen der Rechnung. Bitte versuchen Sie es erneut.'];
        }
    }

    private function buildInvoicePayload(array $data): array
    {
        $plan = $data['plan'];
        $days = $data['days'];
        $planCost = $data['plan_cost'] ?? 0;
        $specialCost = $data['special_cost'] ?? 0;
        $discountPercent = $data['discount_percent'] ?? 0;
        $paymentMethod = $data['payment_method'] ?? 'Bar';
        $vatPercentage = Preference::get('vat_percentage', 20);

        // Prices are stored as net (VAT exclusive), convert to gross (VAT inclusive) for HelloCash
        $items = [];

        if ($planCost > 0) {
            // Convert net price to gross: gross = net * (1 + vat/100)
            $netPricePerUnit = (float)$plan->price;
            $grossPricePerUnit = $netPricePerUnit * (1 + ($vatPercentage / 100));
            
            $items[] = [
                'item_name' => $plan->title ?? 'Hundepension Plan',
                'item_quantity' => (float)$days,
                'item_price' => round($grossPricePerUnit, 2), 
                'item_taxRate' => (float)$vatPercentage,
            ];
        }

        if ($specialCost > 0) {
            // Convert net special cost to gross
            $netSpecialCost = (float)$specialCost;
            $grossSpecialCost = $netSpecialCost * (1 + ($vatPercentage / 100));
            
            $items[] = [
                'item_name' => 'Zusätzliche Kosten',
                'item_quantity' => 1.0,
                'item_price' => round($grossSpecialCost, 2), 
                'item_taxRate' => (float)$vatPercentage,
            ];
        }

        $payload = [
            'invoice_testMode' => $this->testMode,
            'invoice_paymentMethod' => $paymentMethod,
            'invoice_type' => 'pdf',
            'locale' => 'de_AT',
            'signature_mandatory' => $this->signatureMandatory,
            'invoice_text' => 'Vielen Dank für Ihren Besuch!',
            'items' => $items,
        ];

        $hellocashCustomerId = $data['hellocash_customer_id'] ?? null;
        if (!empty($hellocashCustomerId)) {
            $payload['invoice_user_id'] = $hellocashCustomerId;
        }

        if ($discountPercent > 0) {
            $payload['invoice_discount_percent'] = (int)$discountPercent;
        }

        return $payload;
    }

    public function getInvoicePdf(int $invoiceId, string $locale = 'de_AT', bool $cancellation = false): array
    {
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

    private function saveInvoice(array $data): ?HelloCashInvoice
    {
        try {
            $pdfContent = base64_decode($data['invoice_pdf_base64']);
            if ($pdfContent === false) {
                Log::error('Failed to decode invoice PDF from base64', [
                    'hellocash_invoice_id' => $data['hellocash_invoice_id'] ?? null,
                ]);
                return null;
            }

            $invoiceDate = now();
            if (!empty($data['payment_id'])) {
                $payment = Payment::find($data['payment_id']);
                if ($payment && $payment->created_at) {
                    $invoiceDate = $payment->created_at;
                }
            }

            $year = $invoiceDate->format('Y');
            $month = $invoiceDate->format('m');
            $filename = 'invoice_' . $data['hellocash_invoice_id'] . '.pdf';
            $filePath = "invoices/{$year}/{$month}/{$filename}";

            Storage::disk('local')->put($filePath, $pdfContent);

            $invoice = HelloCashInvoice::create([
                'hellocash_invoice_id' => $data['hellocash_invoice_id'],
                'invoice_type' => 'cashier',
                'reservation_id' => $data['reservation_id'],
                'payment_id' => $data['payment_id'],
                'file_path' => $filePath,
            ]);

            if ($invoice->created_at->format('Y-m') !== $invoiceDate->format('Y-m')) {
                $correctYear = $invoice->created_at->format('Y');
                $correctMonth = $invoice->created_at->format('m');
                $correctPath = "invoices/{$correctYear}/{$correctMonth}/{$filename}";
                
                // Move file to correct location
                if (Storage::disk('local')->exists($filePath)) {
                    Storage::disk('local')->move($filePath, $correctPath);
                    $invoice->update(['file_path' => $correctPath]);
                }
            }

            return $invoice;
        } catch (Exception $e) {
            Log::error('Failed to save invoice to local storage', [
                'hellocash_invoice_id' => $data['hellocash_invoice_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function createUser(Customer $customer): array
    {
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
}
