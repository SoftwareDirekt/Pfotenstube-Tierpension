<?php

namespace App\Services;

use App\Models\HelloCashInvoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Preference;
use App\Models\User;
use App\Models\Customer;
use App\Helpers\VATCalculator;
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
     * Regenerate an existing local invoice with manual overrides.
     */
    public function regenerateLocalInvoice(
        HelloCashInvoice $invoice,
        Reservation $reservation,
        Payment $payment,
        array $overrideData = []
    ): array {
        try {
            $days = max(1, (int)($overrideData['days'] ?? $payment->days ?? 1));
            $invoiceAmount = round((float)($overrideData['invoice_amount'] ?? $payment->cost ?? 0), 2);

            $vatPercentage = Preference::get('vat_percentage', 20);
            $netAmount = round(VATCalculator::getNetFromGross($invoiceAmount, $vatPercentage), 2);
            $vatAmount = round($invoiceAmount - $netAmount, 2);

            // Keep optional special cost if possible; cap to total to avoid negative plan costs.
            $specialCost = round((float)($payment->special_cost ?? 0), 2);
            if ($specialCost > $invoiceAmount) {
                $specialCost = $invoiceAmount;
            }
            $planCost = round(max(0, $invoiceAmount - $specialCost), 2);

            $payment->update([
                'days' => $days,
                'cost' => $invoiceAmount,
                'net_amount' => $netAmount,
                'vat_amount' => $vatAmount,
                'plan_cost' => $planCost,
                'special_cost' => $specialCost,
            ]);

            $reservation->refresh();
            $payment->refresh();

            $invoiceNumberFormatted = $invoice->formatted_invoice_number;
            if (empty($invoiceNumberFormatted) || $invoiceNumberFormatted === 'N/A') {
                $invoiceNumberFormatted = $this->formatInvoiceNumber($this->getNextInvoiceNumber());
            }

            $invoiceViewData = $this->prepareInvoiceData(
                $reservation,
                $payment,
                ['days' => $days],
                $invoiceNumberFormatted,
                (int)($invoice->invoice_number ?? 0)
            );

            $pdf = Pdf::loadView('admin.invoices.partials.pdf', $invoiceViewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96,
                ]);

            $pdfContent = $pdf->output();

            $invoiceDate = $invoice->created_at ?? now();
            $year = $invoiceDate->format('Y');
            $month = $invoiceDate->format('m');

            $filePath = $invoice->file_path;
            if (empty($filePath)) {
                $filename = 'invoice_' . str_replace('-', '_', $invoiceNumberFormatted) . '.pdf';
                $filePath = "local_invoices/{$year}/{$month}/{$filename}";
            }

            Storage::disk('local')->put($filePath, $pdfContent);
            $invoice->update(['file_path' => $filePath]);

            return [
                'success' => true,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumberFormatted,
                'file_path' => $filePath,
            ];
        } catch (\Exception $e) {
            Log::error('Local invoice regeneration failed', [
                'invoice_id' => $invoice->id,
                'reservation_id' => $reservation->id ?? null,
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Fehler beim Neu-Generieren der Rechnung: ' . $e->getMessage(),
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
        
        // Prefer persisted payment values to guarantee that manual checkout overrides
        // are reflected 1:1 in generated invoices.
        $planCost = (float)($payment->plan_cost ?? ($invoiceData['plan_cost'] ?? 0));
        $specialCost = (float)($payment->special_cost ?? ($invoiceData['special_cost'] ?? 0));
        $discountPercentage = (int)($payment->discount ?? ($invoiceData['discount_percentage'] ?? 0));
        $discountAmount = (float)($payment->discount_amount ?? 0);
        $days = max(1, (int)($invoiceData['days'] ?? $payment->days ?? 1));

        $grossTotal = round((float)($payment->cost ?? 0), 2);
        $netTotal = round((float)($payment->net_amount ?? 0), 2);
        $vatAmount = round((float)($payment->vat_amount ?? 0), 2);

        // Fallback for legacy rows where calculated fields are missing.
        if ($grossTotal <= 0 && ($planCost > 0 || $specialCost > 0)) {
            $vatMode = config('app.vat_calculation_mode', 'exclusive');
            if ($vatMode === 'inclusive') {
                $netPlanCost = VATCalculator::getNetFromGross($planCost, $vatPercentage);
                $netSpecialCost = VATCalculator::getNetFromGross($specialCost, $vatPercentage);
                $netTotal = $netPlanCost + $netSpecialCost;
            } else {
                $netTotal = $planCost + $specialCost;
            }

            if ($discountPercentage > 0) {
                $discountAmount = round(($discountPercentage / 100) * $netTotal, 2);
                $netTotal = $netTotal - $discountAmount;
            }

            $vatAmount = round(VATCalculator::calculateVATAmount($netTotal, $vatPercentage), 2);
            $grossTotal = round($netTotal + $vatAmount, 2);
        }

        if ($grossTotal > 0 && $netTotal <= 0) {
            $netTotal = round(VATCalculator::getNetFromGross($grossTotal, $vatPercentage), 2);
        }
        if ($grossTotal > 0 && $vatAmount <= 0) {
            $vatAmount = round($grossTotal - $netTotal, 2);
        }
        
        // Company information from authenticated user table
        $user = Auth::user();
        $companyName = $user->company_name ?? '';
        $companyEmail = $user->company_email ?? '';
        $companyAddress = $this->formatUserAddress($user);
        $companyPhone = $user->phone ?? '';
        $companyIban = $user->iban ?? '';
        $companyBic = $user->bic ?? '';
        
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
                'iban' => $companyIban,
                'bic' => $companyBic,
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
                    'unit_price' => $days > 0 ? ($planCost / $days) : $planCost,
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

    /**
     * Generate grouped invoice for multiple reservations belonging to the same customer
     * 
     * @param Customer $customer
     * @param array $reservationData Array of reservation data with keys: reservation, payment, plan_cost, special_cost, discount_percentage, days
     * @param array $reservationIds Array of reservation IDs for the grouped invoice
     * @return array
     */
    public function generateGroupedInvoice(Customer $customer, array $reservationData, array $reservationIds = []): array
    {
        try {
            if (empty($reservationData)) {
                throw new \Exception('Keine Reservierungen für gruppierte Rechnung');
            }

            $vatPercentage = Preference::get('vat_percentage', 20);
            
            // Calculate totals across all reservations
            $totalPlanCost = 0;
            $totalSpecialCost = 0;
            $totalDiscountAmount = 0;
            $totalNetAmount = 0;
            $totalVatAmount = 0;
            $totalGrossAmount = 0;
            
            $invoiceItems = [];
            
            foreach ($reservationData as $index => $data) {
                $reservation = $data['reservation'];
                $payment = $data['payment'];
                
                // Use payment data directly (already calculated during checkout)
                $planCost = $payment->plan_cost ?? 0;
                $specialCost = $payment->special_cost ?? 0;
                $discountPercentage = $payment->discount ?? 0;
                $discountAmount = $payment->discount_amount ?? 0;
                $days = $payment->days ?? 1;
                $netAmount = $payment->net_amount ?? 0;
                $vatAmount = $payment->vat_amount ?? 0;
                $grossAmount = $payment->cost ?? 0;
                
                // Accumulate totals
                $totalPlanCost += $planCost;
                $totalSpecialCost += $specialCost;
                $totalDiscountAmount += $discountAmount;
                $totalNetAmount += $netAmount;
                $totalVatAmount += $vatAmount;
                $totalGrossAmount += $grossAmount;
                
                // Store item data for invoice view
                $invoiceItems[] = [
                    'reservation_id' => $reservation->id,
                    'payment_id' => $payment->id,
                    'plan_cost' => $planCost,
                    'special_cost' => $specialCost,
                    'discount_percentage' => $discountPercentage,
                    'discount_amount' => $discountAmount,
                    'days' => $days,
                    'net_amount' => $netAmount,
                    'vat_amount' => $vatAmount,
                    'gross_amount' => $grossAmount,
                ];
            }
            
            // Get invoice number
            $invoiceNumber = $this->getNextInvoiceNumber();
            $invoiceNumberFormatted = $this->formatInvoiceNumber($invoiceNumber);
            
            // Prepare data for grouped invoice view
            $invoiceViewData = $this->prepareGroupedInvoiceData($customer, $invoiceItems, $invoiceNumberFormatted, [
                'total_net' => $totalNetAmount,
                'total_vat' => $totalVatAmount,
                'total_gross' => $totalGrossAmount,
            ]);
            
            // Generate PDF
            $pdf = Pdf::loadView('admin.invoices.partials.grouped-pdf', $invoiceViewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96,
                ]);
            
            $pdfContent = $pdf->output();
            
            // Save invoice
            $invoiceDate = now();
            if (!empty($reservationData[0]['payment']->created_at)) {
                $invoiceDate = $reservationData[0]['payment']->created_at;
            }
            
            $year = $invoiceDate->format('Y');
            $month = $invoiceDate->format('m');
            
            $storagePath = storage_path('app/local_invoices/' . $year . '/' . $month);
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            
            $filename = 'invoice_' . str_replace('-', '_', $invoiceNumberFormatted) . '.pdf';
            $filePath = "local_invoices/{$year}/{$month}/{$filename}";
            
            Storage::disk('local')->put($filePath, $pdfContent);
            
            // Create invoice record
            $invoice = HelloCashInvoice::create([
                'hellocash_invoice_id' => null,
                'invoice_type' => 'local',
                'invoice_number' => $invoiceNumber,
                'reservation_id' => null, // Grouped invoice doesn't have single reservation
                'payment_id' => null, // Grouped invoice doesn't have single payment
                'customer_id' => $customer->id,
                'is_grouped' => true,
                'reservation_ids' => !empty($reservationIds) ? $reservationIds : array_column($invoiceItems, 'reservation_id'),
                'file_path' => $filePath,
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
            Log::error('Grouped invoice generation failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Fehler beim Erstellen der gruppierten Rechnung: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Prepare grouped invoice data for view
     */
    private function prepareGroupedInvoiceData(Customer $customer, array $invoiceItems, string $invoiceNumberFormatted, array $totals): array
    {
        $vatPercentage = Preference::get('vat_percentage', 20);
        
        // Company information from authenticated user table
        $user = Auth::user();
        $companyName = $user->company_name ?? '';
        $companyEmail = $user->company_email ?? '';
        $companyAddress = $this->formatUserAddress($user);
        $companyPhone = $user->phone ?? '';
        $companyIban = $user->iban ?? '';
        $companyBic = $user->bic ?? '';
        
        // Only set picture if it exists and is not the default placeholder
        $companyPictureBase64 = null;
        if (!empty($user->picture) && $user->picture != 'no-user-picture.gif') {
            $picturePath = public_path('uploads/users/' . $user->picture);
            if (file_exists($picturePath)) {
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
        
        // Prepare items for view (use payment data directly)
        $viewItems = [];
        foreach ($invoiceItems as $item) {
            $reservation = Reservation::with(['dog', 'plan'])->find($item['reservation_id']);
            
            $viewItems[] = [
                'reservation' => $reservation,
                'dog_name' => $reservation->dog->name ?? 'Unbekannt',
                'plan_name' => $reservation->plan->title ?? '-',
                'days' => $item['days'],
                'plan_cost' => $item['plan_cost'],
                'special_cost' => $item['special_cost'],
                'discount_percentage' => $item['discount_percentage'],
                'discount_amount' => $item['discount_amount'],
                'net_amount' => $item['net_amount'],
                'vat_amount' => $item['vat_amount'],
                'gross_amount' => $item['gross_amount'],
            ];
        }
        
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
                'iban' => $companyIban,
                'bic' => $companyBic,
            ],
            'customer' => [
                'type' => $customerType,
                'name' => $customerFullName,
                'address' => $customerAddress,
                'country' => $customer->land ?? 'AT',
            ],
            'items' => $viewItems,
            'totals' => [
                'net' => $totals['total_net'],
                'vat' => $totals['total_vat'],
                'gross' => $totals['total_gross'],
            ],
            'vat_percentage' => $vatPercentage,
            'reservation_count' => count($invoiceItems),
        ];
    }
}
