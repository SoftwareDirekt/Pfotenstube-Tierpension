<?php

namespace App\Services;

use App\Models\HelloCashInvoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Preference;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BankInvoiceService
{
    /**
     * Generate and save a Bank transfer invoice PDF
     * 
     * @param Reservation $reservation
     * @param Payment $payment
     * @param array $invoiceData
     * @return array
     */
    public function generateInvoice(Reservation $reservation, Payment $payment, array $invoiceData): array
    {
        try {
            // Get invoice number in format UEB-01, UEB-02, etc.
            $invoiceNumber = $this->getNextInvoiceNumber();
            $invoiceNumberFormatted = $this->formatInvoiceNumber($invoiceNumber);
            
            // Prepare data for invoice view
            $invoiceViewData = $this->prepareInvoiceData($reservation, $payment, $invoiceData, $invoiceNumberFormatted, $invoiceNumber);
            
            // Generate PDF
            $pdf = Pdf::loadView('admin.invoices.partials.pdf', $invoiceViewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96,
                ]);
            
            $pdfContent = $pdf->output();
            
            // Save invoice (store numeric part in DB, formatted in filename)
            $invoice = $this->saveInvoice([
                'invoice_number' => $invoiceNumber, // Numeric part (1, 2, 3, etc.)
                'invoice_number_formatted' => $invoiceNumberFormatted, // Formatted (UEB-01, UEB-02, etc.)
                'invoice_pdf_content' => $pdfContent,
                'reservation_id' => $reservation->id,
                'payment_id' => $payment->id,
            ]);
            
            return [
                'success' => true,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumberFormatted,
                'invoice_number_numeric' => $invoiceNumber,
                'invoice_pdf_base64' => base64_encode($pdfContent),
                'file_path' => $invoice->file_path,
            ];
        } catch (\Exception $e) {
            Log::error('Bank invoice generation failed', [
                'reservation_id' => $reservation->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Fehler beim Erstellen der Rechnung: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Prepare invoice data for view
     */
    private function prepareInvoiceData(Reservation $reservation, Payment $payment, array $invoiceData, string $invoiceNumberFormatted, int $invoiceNumberNumeric): array
    {
        $customer = $reservation->dog->customer;
        $plan = $reservation->plan;
        $vatPercentage = Preference::get('vat_percentage', 20);
        
        // Calculate amounts
        $planCost = $invoiceData['plan_cost'] ?? 0;
        $specialCost = $invoiceData['special_cost'] ?? 0;
        $discountPercentage = $invoiceData['discount_percentage'] ?? 0;
        $days = $invoiceData['days'] ?? 1;
        
        // Calculate net amounts (prices are stored as net)
        $netPlanCost = $planCost;
        $netSpecialCost = $specialCost;
        $netTotal = $netPlanCost + $netSpecialCost;
        
        // Apply discount to net total
        $discountAmount = 0;
        if ($discountPercentage > 0) {
            $discountAmount = ($discountPercentage / 100) * $netTotal;
            $netTotal = $netTotal - $discountAmount;
        }
        
        // Calculate VAT
        $vatAmount = ($netTotal * $vatPercentage) / 100;
        $grossTotal = $netTotal + $vatAmount;
        
        // Company information from authenticated user table
        $user = Auth::user();
        $companyName = $user->company_name ?? '';
        $companyEmail = $user->company_email ?? '';
        $companyAddress = $this->formatUserAddress($user);
        $companyPhone = $user->phone ?? '';
        
        // Only set picture if it exists and is not the default placeholder
        $companyPictureBase64 = null;
        if (!empty($user->picture) && $user->picture != 'no-user-picture.gif') {
            $picturePath = public_path('uploads/users/' . $user->picture);
            if (file_exists($picturePath)) {
                // Encode image as base64 for reliable DomPDF rendering
                try {
                    $imageData = file_get_contents($picturePath);
                    $imageInfo = getimagesize($picturePath);
                    if ($imageInfo !== false) {
                        $mimeType = $imageInfo['mime'];
                        $companyPictureBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    }
                } catch (\Exception $e) {
                    // Silently fail if image encoding fails
                }
            }
        }
        
        // Format customer name
        $customerTitle = $customer->title ?? '';
        $customerName = $customer->name ?? '';
        $customerFullName = trim($customerTitle . ' ' . $customerName);
        
        // Format customer address
        $customerAddress = trim(($customer->street ?? '') . ', ' . ($customer->zipcode ?? '') . ' ' . ($customer->city ?? ''));
        if (empty($customer->street) && empty($customer->zipcode) && empty($customer->city)) {
            $customerAddress = '';
        }
        
        // Format customer type
        $customerType = $customer->type ?? 'Stammkunde';
        
        return [
            'invoice_number' => $invoiceNumberFormatted,
            'invoice_date' => Carbon::now()->format('d.m.Y H:i:s'),
            'payment_method' => 'Banküberweisung',
            'company' => [
                'picture_base64' => $companyPictureBase64, 
                'name' => $companyName,
                'address' => $companyAddress,
                'phone' => $companyPhone,
                'email' => $companyEmail,
            ],
            'customer' => [
                'type' => $customerType,
                'name' => $customerFullName,
                'address' => $customerAddress,
                'country' => $customer->land ?? 'AT',
            ],
            'items' => [
                [
                    'quantity' => $days,
                    'description' => $plan->title ?? 'Hundepension Plan',
                    'vat_percentage' => $vatPercentage,
                    'unit_price' => $planCost / $days,
                    'total_price' => $planCost,
                ],
                ...($specialCost > 0 ? [[
                    'quantity' => 1,
                    'description' => 'Zusätzliche Kosten',
                    'vat_percentage' => $vatPercentage,
                    'unit_price' => $specialCost,
                    'total_price' => $specialCost,
                ]] : []),
            ],
            'totals' => [
                'net' => $netTotal,
                'vat' => $vatAmount,
                'gross' => $grossTotal,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
            ],
            'vat_breakdown' => [
                [
                    'vat_percentage' => $vatPercentage,
                    'net' => $netTotal,
                    'vat_amount' => $vatAmount,
                    'gross' => $grossTotal,
                ],
            ],
        ];
    }
    
    /**
     * Get next invoice number
     * Starts from 1 and increments sequentially
     */
    private function getNextInvoiceNumber(): int
    {
        // Get the last local invoice
        $lastBankInvoice = HelloCashInvoice::where('invoice_type', 'local')
            ->whereNotNull('invoice_number')
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        // Continue from last invoice number or start from 1
        if ($lastBankInvoice && $lastBankInvoice->invoice_number) {
            return (int)$lastBankInvoice->invoice_number + 1;
        }
        
        return 1;
    }
    
    /**
     * Format invoice number as UEB-01, UEB-02, etc.
     * Always uses 2 digits for consistency (01, 02, ..., 99, then 100, 101, etc.)
     */
    private function formatInvoiceNumber(int $number): string
    {
        // Use 2 digits for numbers 1-99, then natural numbers for 100+
        if ($number < 100) {
            return 'UEB-' . str_pad($number, 2, '0', STR_PAD_LEFT);
        }
        // For 100+, use natural number format (UEB-100, UEB-101, etc.)
        return 'UEB-' . $number;
    }
    
    /**
     * Format user address from address, city, and country fields
     * Returns empty string if no address data is available
     */
    private function formatUserAddress(?User $user): string
    {
        if (!$user) {
            return '';
        }
        
        $addressParts = array_filter([
            $user->address,
            $user->city,
            $user->country,
        ]);
        
        if (empty($addressParts)) {
            return '';
        }
        
        return implode(', ', $addressParts);
    }
    
    /**
     * Save invoice to database and storage
     */
    private function saveInvoice(array $data): HelloCashInvoice
    {
        $invoiceDate = now();
        if (!empty($data['payment_id'])) {
            $payment = Payment::find($data['payment_id']);
            if ($payment && $payment->created_at) {
                $invoiceDate = $payment->created_at;
            }
        }
        
        $year = $invoiceDate->format('Y');
        $month = $invoiceDate->format('m');

        // Ensure storage directory exists
        $storagePath = storage_path('app/local_invoices/' . $year . '/' . $month);
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Use formatted invoice number for filename
        $filename = 'invoice_' . str_replace('-', '_', $data['invoice_number_formatted']) . '.pdf';
        $filePath = "local_invoices/{$year}/{$month}/{$filename}";
        
        Storage::disk('local')->put($filePath, $data['invoice_pdf_content']);
        
        $invoice = HelloCashInvoice::create([
            'hellocash_invoice_id' => null, 
            'invoice_type' => 'local',
            'invoice_number' => $data['invoice_number'], 
            'reservation_id' => $data['reservation_id'],
            'payment_id' => $data['payment_id'],
            'file_path' => $filePath,
        ]);
        
        return $invoice;
    }
}
