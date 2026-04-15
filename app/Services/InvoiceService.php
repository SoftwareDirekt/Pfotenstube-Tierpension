<?php

namespace App\Services;

use App\Helpers\VATCalculator;
use App\Models\Invoice;
use App\Models\Preference;
use App\Models\Reservation;
use App\Models\ReservationGroup;
use App\Models\ReservationGroupEntry;
use App\Models\ReservationPaymentEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoiceService
{
    /**
     * Generate and save a local invoice PDF for one or more entries.
     *
     * @param Reservation $reservation
     * @param ReservationPaymentEntry|array|\Illuminate\Support\Collection $entries
     * @return array
     */
    /**
     * @param  string|null  $forcedType  When supplied, overrides the per-entry type detection.
     *                                   Use 'final' for the system-generated Interne invoice (all entries),
     *                                   'advance' for a standalone advance receipt.
     * @param  float|null  $advanceLineDisplayGross  When set (e.g. after check-in correction), the PDF line uses this gross (capped stay total) instead of the ledger entry amount.
     */
    public function generateInvoice(Reservation $reservation, $entries, ?string $forcedType = null, ?float $advanceLineDisplayGross = null): array
    {
        try {
            if (!is_array($entries) && !($entries instanceof \Illuminate\Support\Collection)) {
                $entries = collect([$entries]);
            } else {
                $entries = collect($entries);
            }

            $primaryEntry = $entries->first();
            $invoiceNumberFormatted = $this->getNextInvoiceNumber();

            $invoiceViewData = $this->prepareInvoiceData($reservation, $entries, $invoiceNumberFormatted, $advanceLineDisplayGross);

            $pdf = Pdf::loadView('admin.invoices.partials.pdf', $invoiceViewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96,
                ]);

            $pdfContent = $pdf->output();

            // $forcedType lets the caller pin the DB type (e.g. 'final' for the Interne invoice
            // at checkout, where the primary entry might be 'advance' and would mislead detection).
            $resolvedType = $forcedType ?? ($primaryEntry?->type ?? 'final');

            $invoice = $this->saveInvoice([
                'invoice_number' => $invoiceNumberFormatted,
                'invoice_pdf_content' => $pdfContent,
                'reservation_id' => $reservation->id,
                'res_payment_entry_id' => $primaryEntry?->id,
                'customer_id' => $reservation->dog?->customer_id,
                'type' => $resolvedType,
                'status' => 'paid',
            ]);

            $this->invalidatePriorLocalInvoicesForReservation($reservation, $invoice);

            return [
                'success' => true,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumberFormatted,
                'invoice_pdf_base64' => base64_encode($pdfContent),
                'file_path' => $invoice->file_path,
            ];
        } catch (\Exception $e) {
            Log::error('Invoice generation failed', [
                'reservation_id' => $reservation->id,
                'entries_count' => $entries instanceof \Illuminate\Support\Collection ? $entries->count() : 0,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Fehler beim Erstellen der Rechnung: ' . $e->getMessage(),
            ];
        }
    }

    private function prepareInvoiceData(Reservation $reservation, $currentEntries, string $invoiceNumberFormatted, ?float $advanceLineDisplayGross = null): array
    {
        if (! $currentEntries instanceof \Illuminate\Support\Collection) {
            $currentEntries = collect($currentEntries);
        }

        if ($advanceLineDisplayGross !== null && $currentEntries->isNotEmpty()) {
            return $this->prepareSingleLineAdvanceReceiptData(
                $reservation,
                $currentEntries->first(),
                $invoiceNumberFormatted,
                max(0.0, round((float) $advanceLineDisplayGross, 2)),
                (int) Preference::get('vat_percentage', 20)
            );
        }

        $customer = $reservation->dog?->customer;
        $plan = $reservation->plan;
        $vatPercentage = Preference::get('vat_percentage', 20);

        $allEntries = $reservation->reservationPayment
            ? $reservation->reservationPayment->entries()->where('status', 'active')->orderBy('transaction_date', 'asc')->get()
            : collect();

        $sortedEntries = $allEntries->sortBy(function ($e) {
            return [$e->transaction_date ? $e->transaction_date->timestamp : 0, $e->id];
        })->values();

        $splitBatch = $this->paymentEntriesLookLikeSplitBatch($allEntries);

        $planTitle = $plan?->title ?? 'Hundepension';
        if ($planTitle !== '') {
            $planTitle = html_entity_decode($planTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Pre-build additional cost rows so we can split them from the last Zahlung item.
        $additionalCostModels    = $reservation->additionalCosts()->get();
        $additionalCostsGross    = 0.0;
        $additionalCostRows      = [];

        foreach ($additionalCostModels as $ac) {
            $qty       = max(1, (int) ($ac->quantity ?? 1));
            $acGross   = round(VATCalculator::calculate((float) $ac->price * $qty, $vatPercentage)['gross'], 2);
            $acVatData = VATCalculator::calculate($acGross, $vatPercentage);
            $acUnitNet = round(VATCalculator::calculate((float) $ac->price, $vatPercentage)['net'], 2);

            $additionalCostsGross += $acGross;

            $additionalCostRows[] = [
                'quantity'        => $qty,
                'description'     => $ac->title,
                'vat_percentage'  => $vatPercentage,
                'unit_price'      => $acUnitNet,
                'vat_amount'      => round($acVatData['vat'], 2),
                'total_price'     => round($acVatData['net'], 2),
                'gross'           => $acGross,
                'date'            => null,
                'is_current'      => false,
                'overpaid_amount' => null,
            ];
        }
        $additionalCostsGross = round($additionalCostsGross, 2);

        $items              = [];
        $grossTotal         = 0;
        $refundTotal        = 0;
        $lastZahlungIdx     = null;

        foreach ($allEntries as $entry) {
            $dogName = $reservation->dog?->name ?? 'Hund';
            $period  = $reservation->checkin_date->format('d.m') . ' - ' . $reservation->checkout_date->format('d.m.Y');

            $showAsAnzahlung = $this->entryShouldShowAsAnzahlung($entry, $sortedEntries, $splitBatch);
            $typeLabel       = $showAsAnzahlung ? 'Anzahlung' : 'Zahlung';
            $description     = $typeLabel . ': ' . $planTitle . " ($dogName, $period)";

            if ($entry->note) {
                $description .= ' [' . $entry->note . ']';
            }

            $vatData = VATCalculator::calculate((float) $entry->amount, $vatPercentage);

            if (!$showAsAnzahlung) {
                $lastZahlungIdx = count($items);
            }

            $items[] = [
                'quantity'       => 1,
                'description'    => $description,
                'vat_percentage' => $vatPercentage,
                'unit_price'     => $vatData['net'],
                'vat_amount'     => $vatData['vat'],
                'total_price'    => $vatData['net'],
                'gross'          => $entry->amount,
                'date'           => $entry->transaction_date?->format('d.m.Y'),
                'is_current'     => $currentEntries->contains('id', $entry->id),
                'overpaid_amount' => $entry->overpaid_amount,
            ];

            $grossTotal  += (float) $entry->amount;
            $refundTotal += (float)($entry->overpaid_amount ?? 0);
        }

        // Split additional costs out of the last Zahlung line so they appear as separate items.
        if ($lastZahlungIdx !== null && $additionalCostsGross > 0.005) {
            $zahlungGross  = (float) $items[$lastZahlungIdx]['gross'];
            $adjustedGross = round($zahlungGross - $additionalCostsGross, 2);

            // Only split when the result is >= 0 (guard against edge-case over-advance)
            if ($adjustedGross >= 0.0) {
                if ($adjustedGross <= 0.005) {
                    // If the split consumes the whole payment line, drop that zero row.
                    array_splice($items, $lastZahlungIdx, 1);
                } else {
                    $adjVatData = VATCalculator::calculate($adjustedGross, $vatPercentage);
                    $items[$lastZahlungIdx]['gross']      = $adjustedGross;
                    $items[$lastZahlungIdx]['unit_price'] = $adjVatData['net'];
                    $items[$lastZahlungIdx]['vat_amount'] = $adjVatData['vat'];
                    $items[$lastZahlungIdx]['total_price']= $adjVatData['net'];
                }

                // Append each additional cost as its own row.
                // grossTotal is NOT changed — the additional costs were already part of the original payment amounts.
                foreach ($additionalCostRows as $acRow) {
                    $items[] = $acRow;
                }
            }
        }

        // Never render meaningless 0.00 rows in internal invoices.
        $items = collect($items)
            ->filter(fn ($row) => abs((float) ($row['gross'] ?? 0)) > 0.005)
            ->values()
            ->toArray();

        $finalVatData = VATCalculator::calculate($grossTotal, $vatPercentage);
        $netTotal = $finalVatData['net'];
        $vatAmount = $finalVatData['vat'];

        $user = Auth::user();
        $companyName = $user->company_name ?? '';
        $companyEmail = $user->company_email ?? '';
        $companyAddress = $this->formatUserAddress($user);
        $companyPhone = $user->phone ?? '';
        $companyIban = $user->iban ?? '';
        $companyBic = $user->bic ?? '';

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
                }
            }
        }

        $customerTitle = $customer?->title ?? '';
        $customerName = $customer?->name ?? '';
        $customerFullName = trim($customerTitle . ' ' . $customerName);

        $customerAddress = trim(($customer?->street ?? '') . ', ' . ($customer?->zipcode ?? '') . ' ' . ($customer?->city ?? ''));
        if (empty($customer?->street) && empty($customer?->zipcode) && empty($customer?->city)) {
            $customerAddress = '';
        }

        if ($currentEntries->count() === 1) {
            $method = $currentEntries->first()->method;
            $paymentMethodText = $this->formatPaymentMethod($method);
        } else {
            $methods = $currentEntries->pluck('method')->unique()->map(function ($method) {
                return $this->formatPaymentMethod($method);
            })->toArray();
            $paymentMethodText = implode(' / ', $methods);
        }

        return [
            'invoice_number' => $invoiceNumberFormatted,
            'invoice_date' => Carbon::now()->format('d.m.Y H:i:s'),
            'payment_method' => $paymentMethodText,
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
                'type' => $customer?->type ?? 'Stammkunde',
                'name' => $customerFullName,
                'address' => $customerAddress,
                'country' => $customer?->land ?? 'AT',
            ],
            'items' => $items,
            'totals' => [
                'net'                 => $netTotal,
                'vat'                 => $vatAmount,
                'gross'               => $grossTotal,
                'refund'              => $refundTotal > 0 ? $refundTotal : null,
                'discount_percentage' => 0,
                'discount_amount'     => 0,
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
     * One-line advance receipt where the shown gross is the corrected stay total (not the full ledger payment).
     */
    private function prepareSingleLineAdvanceReceiptData(
        Reservation $reservation,
        ReservationPaymentEntry $entry,
        string $invoiceNumberFormatted,
        float $displayGross,
        int $vatPercentage
    ): array {
        $customer = $reservation->dog?->customer;
        $plan = $reservation->plan;
        $planTitle = $plan?->title ?? 'Hundepension';
        if ($planTitle !== '') {
            $planTitle = html_entity_decode($planTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $dogName = $reservation->dog?->name ?? 'Hund';
        $period = ($reservation->checkin_date && $reservation->checkout_date)
            ? $reservation->checkin_date->format('d.m').' - '.$reservation->checkout_date->format('d.m.Y')
            : '';

        $gross = round($displayGross, 2);
        $vatData = VATCalculator::calculate($gross, $vatPercentage);
        $entryPaid = round((float) $entry->amount, 2);
        $paidNote = '';
        if ($gross <= 0.005 && $entryPaid > 0.005) {
            $paidNote = ' Hinweis: laut Kasse gebucht '.$this->formatMoneyDe($entryPaid).' €; kein weiterer anteiliger Aufenthaltsbetrag auf dieser Anzahlung - Abwicklung (z. B. Rückzahlung) am Kassenplatz.';
        } elseif ($entryPaid > $gross + 0.01) {
            $paidNote = ' Hinweis: laut Kasse gebucht '.$this->formatMoneyDe($entryPaid).' € - Rechnungssumme = korrigierter Leistungsbetrag nach Aufenthalt.';
        }
        $description = 'Anzahlung: '.$planTitle.' ('.$dogName.', '.$period.')'.$paidNote;

        $items = [[
            'quantity'        => 1,
            'description'     => $description,
            'vat_percentage'  => $vatPercentage,
            'unit_price'      => $vatData['net'],
            'vat_amount'      => $vatData['vat'],
            'total_price'     => $vatData['net'],
            'gross'           => $gross,
            'date'            => $entry->transaction_date?->format('d.m.Y'),
            'is_current'      => true,
            'overpaid_amount' => null,
        ]];

        $grossTotal  = $gross;
        $netTotal    = $vatData['net'];
        $vatAmount   = $vatData['vat'];
        $refundTotal = 0.0;

        $paymentMethodText = $this->formatPaymentMethod($entry->method);

        $user = Auth::user();
        $companyName = $user->company_name ?? '';
        $companyEmail = $user->company_email ?? '';
        $companyAddress = $this->formatUserAddress($user);
        $companyPhone = $user->phone ?? '';
        $companyIban = $user->iban ?? '';
        $companyBic = $user->bic ?? '';

        $companyPictureBase64 = null;
        if (! empty($user->picture) && $user->picture != 'no-user-picture.gif') {
            $picturePath = public_path('uploads/users/'.$user->picture);
            if (file_exists($picturePath)) {
                try {
                    $imageData = file_get_contents($picturePath);
                    $imageInfo = getimagesize($picturePath);
                    if ($imageInfo !== false) {
                        $mimeType = $imageInfo['mime'];
                        $companyPictureBase64 = 'data:'.$mimeType.';base64,'.base64_encode($imageData);
                    }
                } catch (\Exception $e) {
                }
            }
        }

        $customerTitle = $customer?->title ?? '';
        $customerName = $customer?->name ?? '';
        $customerFullName = trim($customerTitle.' '.$customerName);

        $customerAddress = trim(($customer?->street ?? '').', '.($customer?->zipcode ?? '').' '.($customer?->city ?? ''));
        if (empty($customer?->street) && empty($customer?->zipcode) && empty($customer?->city)) {
            $customerAddress = '';
        }

        return [
            'invoice_number' => $invoiceNumberFormatted,
            'invoice_date'   => Carbon::now()->format('d.m.Y H:i:s'),
            'payment_method' => $paymentMethodText,
            'company'        => [
                'picture_base64' => $companyPictureBase64,
                'name'           => $companyName,
                'address'        => $companyAddress,
                'phone'          => $companyPhone,
                'email'          => $companyEmail,
                'iban'           => $companyIban,
                'bic'            => $companyBic,
            ],
            'customer' => [
                'type'    => $customer?->type ?? 'Stammkunde',
                'name'    => $customerFullName,
                'address' => $customerAddress,
                'country' => $customer?->land ?? 'AT',
            ],
            'items' => $items,
            'totals' => [
                'net'                 => $netTotal,
                'vat'                 => $vatAmount,
                'gross'               => $grossTotal,
                'refund'              => $refundTotal > 0 ? $refundTotal : null,
                'discount_percentage' => 0,
                'discount_amount'     => 0,
            ],
            'vat_breakdown' => [
                [
                    'vat_percentage' => $vatPercentage,
                    'net'            => $netTotal,
                    'vat_amount'     => $vatAmount,
                    'gross'          => $grossTotal,
                ],
            ],
        ];
    }

    /**
     * Generate a single invoice PDF for a reservation group payment (advance or Schlussrechnung).
     * Default DB type: advance entry → advance; other entries → checkout (Schlussrechnung), not "final" (Interne).
     */
    public function generateGroupInvoice(ReservationGroup $group, ReservationGroupEntry $entry, ?string $forcedType = null): array
    {
        try {
            $group->loadMissing(['customer', 'reservations.dog', 'reservations.plan', 'reservations.additionalCosts']);

            $invoiceNumber = $this->getNextInvoiceNumber();
            $vatPercentage = (int) Preference::get('vat_percentage', 20);
            $resolvedType  = $forcedType ?? (($entry->type === 'advance') ? 'advance' : 'checkout');

            $viewData = $this->prepareGroupInvoiceData($group, $entry, $invoiceNumber, $vatPercentage, $resolvedType);

            $pdf = Pdf::loadView('admin.invoices.partials.pdf', $viewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'defaultFont'          => 'DejaVu Sans',
                    'dpi'                  => 96,
                ]);

            $pdfContent = $pdf->output();
            $filePath   = $this->storeInvoicePdf($invoiceNumber, $pdfContent);

            $invoice = Invoice::create([
                'invoice_number'             => $invoiceNumber,
                'reservation_id'             => null,
                'reservation_group_id'       => $group->id,
                'reservation_group_entry_id' => $entry->id,
                'customer_id'                => $group->customer_id,
                'type'                       => $resolvedType,
                'file_path'                  => $filePath,
                'status'                     => 'paid',
            ]);

            if ($resolvedType === 'checkout') {
                $this->invalidateGroupInvoicesByType($group, $invoice, 'checkout');
            }

            return [
                'success'        => true,
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'invoice_pdf_base64' => base64_encode($pdfContent),
                'file_path'      => $invoice->file_path,
            ];
        } catch (\Exception $e) {
            Log::error('Group invoice generation failed', [
                'group_id' => $group->id,
                'entry_id' => $entry->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Interne Schlussrechnung for a group: all active group payment entries as line items (like single checkout).
     */
    public function generateGroupInternalInvoice(ReservationGroup $group): array
    {
        try {
            $group->loadMissing(['customer', 'reservations.dog', 'reservations.plan']);
            $entries = $group->activeEntries()->orderBy('transaction_date')->orderBy('id')->get();
            if ($entries->isEmpty()) {
                return ['success' => false, 'error' => 'Keine Zahlungseinträge für die Gruppe'];
            }

            $invoiceNumber = $this->getNextInvoiceNumber();
            $vatPercentage = (int) Preference::get('vat_percentage', 20);
            $viewData      = $this->prepareGroupInternalLedgerViewData($group, $entries, $invoiceNumber, $vatPercentage);

            $pdf = Pdf::loadView('admin.invoices.partials.pdf', $viewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'defaultFont'          => 'DejaVu Sans',
                    'dpi'                  => 96,
                ]);

            $pdfContent = $pdf->output();
            $filePath   = $this->storeInvoicePdf($invoiceNumber, $pdfContent);

            $invoice = Invoice::create([
                'invoice_number'             => $invoiceNumber,
                'reservation_id'             => null,
                'reservation_group_id'       => $group->id,
                'reservation_group_entry_id' => null,
                'customer_id'                => $group->customer_id,
                'type'                       => 'final',
                'file_path'                  => $filePath,
                'status'                     => 'paid',
            ]);

            $this->invalidatePriorFinalInvoicesForGroup($group, $invoice);

            return [
                'success'            => true,
                'invoice_id'         => $invoice->id,
                'invoice_number'     => $invoiceNumber,
                'invoice_pdf_base64' => base64_encode($pdfContent),
                'file_path'          => $invoice->file_path,
            ];
        } catch (\Exception $e) {
            Log::error('Group internal invoice generation failed', [
                'group_id' => $group->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function prepareGroupInvoiceData(ReservationGroup $group, ReservationGroupEntry $entry, string $invoiceNumber, int $vatPercentage, string $invoiceDbType): array
    {
        $customer   = $group->customer;
        $user       = Auth::user();
        $entryGross = round((float) $entry->amount, 2);

        $dogNames  = [];
        $planTitle = 'Hundepension';
        $period    = '';

        foreach ($group->reservations as $res) {
            $dogNames[] = $res->dog?->name ?? 'Hund';

            if ($res->plan?->title) {
                $planTitle = html_entity_decode($res->plan->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if (empty($period) && $res->checkin_date && $res->checkout_date) {
                $period = $res->checkin_date->format('d.m') . ' - ' . $res->checkout_date->format('d.m.Y');
            }
        }

        $typeLabel = match ($invoiceDbType) {
            'advance' => 'Anzahlung',
            'checkout' => 'Schlussrechnung',
            default => $entry->type === 'advance' ? 'Anzahlung' : 'Zahlung',
        };
        $description = $typeLabel . ': ' . $planTitle . ' (' . implode(', ', $dogNames) . ', ' . $period . ')';

        $vatData = VATCalculator::calculate($entryGross, $vatPercentage);

        $items = [[
            'quantity'        => 1,
            'description'     => $description,
            'vat_percentage'  => $vatPercentage,
            'unit_price'      => $vatData['net'],
            'vat_amount'      => $vatData['vat'],
            'total_price'     => $vatData['net'],
            'gross'           => $entryGross,
            'date'            => $entry->transaction_date?->format('d.m.Y'),
            'is_current'      => true,
            'overpaid_amount' => null,
        ]];

        $grossTotal = $entryGross;
        $netTotal   = $vatData['net'];
        $vatAmount  = $vatData['vat'];

        $companyPictureBase64 = null;
        if (! empty($user->picture) && $user->picture != 'no-user-picture.gif') {
            $picturePath = public_path('uploads/users/' . $user->picture);
            if (file_exists($picturePath)) {
                try {
                    $imageData = file_get_contents($picturePath);
                    $imageInfo = getimagesize($picturePath);
                    if ($imageInfo !== false) {
                        $companyPictureBase64 = 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageData);
                    }
                } catch (\Exception $e) {}
            }
        }

        $customerTitle    = $customer?->title ?? '';
        $customerName     = $customer?->name ?? '';
        $customerFullName = trim($customerTitle . ' ' . $customerName);
        $customerAddress  = trim(($customer?->street ?? '') . ', ' . ($customer?->zipcode ?? '') . ' ' . ($customer?->city ?? ''));
        if (empty($customer?->street) && empty($customer?->zipcode) && empty($customer?->city)) {
            $customerAddress = '';
        }

        return [
            'invoice_number' => $invoiceNumber,
            'invoice_date'   => Carbon::now()->format('d.m.Y H:i:s'),
            'payment_method' => $this->formatPaymentMethod($entry->method),
            'company' => [
                'picture_base64' => $companyPictureBase64,
                'name'           => $user->company_name ?? '',
                'address'        => $this->formatUserAddress($user),
                'phone'          => $user->phone ?? '',
                'email'          => $user->company_email ?? '',
                'iban'           => $user->iban ?? '',
                'bic'            => $user->bic ?? '',
            ],
            'customer' => [
                'type'    => $customer?->type ?? 'Stammkunde',
                'name'    => $customerFullName,
                'address' => $customerAddress,
                'country' => $customer?->land ?? 'AT',
            ],
            'items' => $items,
            'totals' => [
                'net'                 => $netTotal,
                'vat'                 => $vatAmount,
                'gross'               => $grossTotal,
                'refund'              => null,
                'discount_percentage' => 0,
                'discount_amount'     => 0,
            ],
            'vat_breakdown' => [
                [
                    'vat_percentage' => $vatPercentage,
                    'net'            => $netTotal,
                    'vat_amount'     => $vatAmount,
                    'gross'          => $grossTotal,
                ],
            ],
        ];
    }

    private function prepareGroupInternalLedgerViewData(ReservationGroup $group, $entries, string $invoiceNumber, int $vatPercentage): array
    {
        $customer = $group->customer;
        $user     = Auth::user();

        $dogNames  = [];
        $planTitle = 'Hundepension';
        $period    = '';

        foreach ($group->reservations as $res) {
            if ($res->dog) {
                $dogNames[] = $res->dog->name;
            }
            if ($res->plan?->title) {
                $planTitle = html_entity_decode($res->plan->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        $dogNames = array_values(array_unique($dogNames));

        $resColl = $group->reservations->filter(fn ($r) => $r->checkin_date && $r->checkout_date);
        if ($resColl->isNotEmpty()) {
            $minIn = $resColl->min(fn ($r) => $r->checkin_date->timestamp);
            $maxOut = $resColl->max(fn ($r) => $r->checkout_date->timestamp);
            $ci = Carbon::createFromTimestamp($minIn);
            $co = Carbon::createFromTimestamp($maxOut);
            $period = $ci->format('d.m') . ' - ' . $co->format('d.m.Y');
        }

        $items       = [];
        $grossTotal  = 0.0;
        $refundTotal = 0.0;

        foreach ($entries as $e) {
            $gross = round((float) $e->amount, 2);
            if (abs($gross) < 0.005) {
                continue;
            }
            $typeLabel = $e->type === 'advance' ? 'Anzahlung' : 'Zahlung';
            $description = $typeLabel . ': ' . $planTitle . ' (' . implode(', ', $dogNames) . ', ' . $period . ')';
            // Keep system checkout note on the DB entry for Kasse details only — not on the internal PDF.
            $internalPdfSkipNotes = ['Restzahlung bei Gruppen-Checkout'];
            if ($e->note && ! in_array(trim((string) $e->note), $internalPdfSkipNotes, true)) {
                $description .= ' [' . $e->note . ']';
            }
            $vatData = VATCalculator::calculate($gross, $vatPercentage);
            $items[] = [
                'quantity'        => 1,
                'description'     => $description,
                'vat_percentage'  => $vatPercentage,
                'unit_price'      => $vatData['net'],
                'vat_amount'      => $vatData['vat'],
                'total_price'     => $vatData['net'],
                'gross'           => $gross,
                'date'            => $e->transaction_date?->format('d.m.Y'),
                'is_current'      => false,
                'overpaid_amount' => $e->overpaid_amount,
            ];
            $grossTotal += $gross;
            $refundTotal += (float) ($e->overpaid_amount ?? 0);
        }

        $grossTotal = round($grossTotal, 2);
        $finalVat   = VATCalculator::calculate($grossTotal, $vatPercentage);
        $netTotal   = $finalVat['net'];
        $vatAmount  = $finalVat['vat'];

        $companyPictureBase64 = null;
        if (! empty($user->picture) && $user->picture != 'no-user-picture.gif') {
            $picturePath = public_path('uploads/users/' . $user->picture);
            if (file_exists($picturePath)) {
                try {
                    $imageData = file_get_contents($picturePath);
                    $imageInfo = getimagesize($picturePath);
                    if ($imageInfo !== false) {
                        $companyPictureBase64 = 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageData);
                    }
                } catch (\Exception $ex) {
                }
            }
        }

        $customerTitle      = $customer?->title ?? '';
        $customerName       = $customer?->name ?? '';
        $customerFullName   = trim($customerTitle . ' ' . $customerName);
        $customerAddress    = trim(($customer?->street ?? '') . ', ' . ($customer?->zipcode ?? '') . ' ' . ($customer?->city ?? ''));
        if (empty($customer?->street) && empty($customer?->zipcode) && empty($customer?->city)) {
            $customerAddress = '';
        }

        $methods = $entries->pluck('method')->unique()->map(fn ($m) => $this->formatPaymentMethod($m))->values()->toArray();
        $paymentMethodText = count($methods) === 1 ? ($methods[0] ?? '') : implode(' / ', $methods);

        return [
            'invoice_number'   => $invoiceNumber,
            'invoice_date'     => Carbon::now()->format('d.m.Y H:i:s'),
            'payment_method'   => $paymentMethodText,
            'company'          => [
                'picture_base64' => $companyPictureBase64,
                'name'           => $user->company_name ?? '',
                'address'        => $this->formatUserAddress($user),
                'phone'          => $user->phone ?? '',
                'email'          => $user->company_email ?? '',
                'iban'           => $user->iban ?? '',
                'bic'            => $user->bic ?? '',
            ],
            'customer' => [
                'type'    => $customer?->type ?? 'Stammkunde',
                'name'    => $customerFullName,
                'address' => $customerAddress,
                'country' => $customer?->land ?? 'AT',
            ],
            'items' => $items,
            'totals' => [
                'net'                 => $netTotal,
                'vat'                 => $vatAmount,
                'gross'               => $grossTotal,
                'refund'              => $refundTotal > 0.01 ? $refundTotal : null,
                'discount_percentage' => 0,
                'discount_amount'     => 0,
            ],
            'vat_breakdown' => [
                [
                    'vat_percentage' => $vatPercentage,
                    'net'            => $netTotal,
                    'vat_amount'     => $vatAmount,
                    'gross'          => $grossTotal,
                ],
            ],
        ];
    }

    private function invalidateGroupInvoicesByType(ReservationGroup $group, Invoice $newInvoice, string $type): void
    {
        Invoice::where('reservation_group_id', $group->id)
            ->where('id', '!=', $newInvoice->id)
            ->where('type', $type)
            ->update(['status' => 'cancelled']);
    }

    private function invalidatePriorFinalInvoicesForGroup(ReservationGroup $group, Invoice $newInvoice): void
    {
        Invoice::where('reservation_group_id', $group->id)
            ->where('id', '!=', $newInvoice->id)
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhere('type', 'final');
            })
            ->update(['status' => 'cancelled']);
    }

    private function formatMoneyDe(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }

    private function getNextInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $lastInvoice = Invoice::lockForUpdate()
                ->where('invoice_number', 'like', 'UEB-%')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastInvoice && $lastInvoice->invoice_number) {
                $lastNumber = (int)preg_replace('/[^0-9]/', '', $lastInvoice->invoice_number);
                return $this->formatInvoiceNumber($lastNumber + 1);
            }

            return $this->formatInvoiceNumber(1);
        });
    }

    private function formatInvoiceNumber(int $number): string
    {
        if ($number < 100) {
            return 'UEB-' . str_pad($number, 2, '0', STR_PAD_LEFT);
        }
        return 'UEB-' . $number;
    }

    private function formatUserAddress(?User $user): string
    {
        if (!$user) {
            return '';
        }

        $parts = array_filter([$user->address, $user->city, $user->country]);
        return implode(', ', $parts);
    }

    private function formatPaymentMethod(?string $method): string
    {
        if ($method === 'Bank' || $method === 'Ueberweisung') {
            return 'Bankueberweisung';
        }
        return $method ?? 'Bar';
    }

    private function saveInvoice(array $data): Invoice
    {
        $filePath = $this->storeInvoicePdf(
            $data['invoice_number'],
            $data['invoice_pdf_content'],
            $data['invoice_date'] ?? null
        );

        return Invoice::create([
            'invoice_number' => $data['invoice_number'],
            'reservation_id' => $data['reservation_id'],
            'res_payment_entry_id' => $data['res_payment_entry_id'],
            'customer_id' => $data['customer_id'],
            'type' => $data['type'],
            'file_path' => $filePath,
            'status' => $data['status'] ?? 'paid',
        ]);
    }

    private function storeInvoicePdf(string $invoiceNumber, string $pdfContent, ?Carbon $invoiceDate = null): string
    {
        $invoiceDate = $invoiceDate ?? Carbon::now();
        $year = $invoiceDate->format('Y');
        $month = $invoiceDate->format('m');

        $storagePath = storage_path('app/invoices/' . $year . '/' . $month);
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filename = 'invoice_' . str_replace('-', '_', $invoiceNumber) . '.pdf';
        $filePath = 'invoices/' . $year . '/' . $month . '/' . $filename;

        Storage::disk('local')->put($filePath, $pdfContent);

        return $filePath;
    }

    private function normalizePaymentEntryType(?string $type): string
    {
        return strtolower(trim((string) $type));
    }

    /**
     * Cash + bank (or similar) created in one checkout: same moment → all lines are "Zahlung", not Anzahlung.
     */
    private function paymentEntriesLookLikeSplitBatch($entries): bool
    {
        if ($entries->count() < 2) {
            return false;
        }

        $minCreated = null;
        $maxCreated = null;
        foreach ($entries as $e) {
            if (! $e->created_at) {
                continue;
            }
            $t = Carbon::parse($e->created_at)->timestamp;
            $minCreated = $minCreated === null ? $t : min($minCreated, $t);
            $maxCreated = $maxCreated === null ? $t : max($maxCreated, $t);
        }
        $createdClose = $minCreated !== null && $maxCreated !== null && ($maxCreated - $minCreated) <= 10;

        $minTx = null;
        $maxTx = null;
        foreach ($entries as $e) {
            $t = $e->transaction_date ? Carbon::parse($e->transaction_date)->timestamp : 0;
            $minTx = $minTx === null ? $t : min($minTx, $t);
            $maxTx = $maxTx === null ? $t : max($maxTx, $t);
        }
        $txClose = $minTx !== null && $maxTx !== null && ($maxTx - $minTx) <= 5;

        return $createdClose || $txClose;
    }

    private function entryShouldShowAsAnzahlung(ReservationPaymentEntry $entry, $sortedEntries, bool $splitBatch): bool
    {
        $rawType = $entry->getRawOriginal('type') ?? $entry->type;
        if ($this->normalizePaymentEntryType($rawType) === 'advance') {
            return true;
        }

        if ($splitBatch || $sortedEntries->count() < 2) {
            return false;
        }

        $last = $sortedEntries->last();

        return $last && (int) $entry->id !== (int) $last->id;
    }

    /**
     * Generate a checkout-specific invoice showing the service breakdown.
     * Line items represent: accommodation plan × days, additional costs.
     * A payment summary section shows advance deductions and the balance due.
     *
     * @param  Reservation  $reservation
     * @param  array  $breakdown  Structured checkout data (see ReservationsController::checkout)
     * @return array
     */
    public function generateCheckoutInvoice(Reservation $reservation, array $breakdown): array
    {
        try {
            $invoiceNumber = $this->getNextInvoiceNumber();
            $invoiceViewData = $this->prepareCheckoutInvoiceData($reservation, $breakdown, $invoiceNumber);

            $pdf = Pdf::loadView('admin.invoices.partials.checkout-pdf', $invoiceViewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'defaultFont'          => 'DejaVu Sans',
                    'dpi'                  => 96,
                ]);

            $pdfContent = $pdf->output();

            $invoice = $this->saveInvoice([
                'invoice_number'       => $invoiceNumber,
                'invoice_pdf_content'  => $pdfContent,
                'reservation_id'       => $reservation->id,
                'res_payment_entry_id' => $breakdown['last_entry_id'] ?? null,
                'customer_id'          => $reservation->dog?->customer_id,
                'type'                 => 'checkout',
                'status'               => 'paid',
            ]);

            // Only replace previous 'checkout' invoices for this reservation
            $this->invalidateInvoicesByType($reservation, $invoice, 'checkout');

            return [
                'success'              => true,
                'invoice_id'           => $invoice->id,
                'invoice_number'       => $invoiceNumber,
                'invoice_pdf_base64'   => base64_encode($pdfContent),
                'file_path'            => $invoice->file_path,
            ];
        } catch (\Exception $e) {
            Log::error('Checkout invoice generation failed', [
                'reservation_id' => $reservation->id,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => 'Fehler beim Erstellen der Abrechnung: ' . $e->getMessage(),
            ];
        }
    }

    private function prepareCheckoutInvoiceData(Reservation $reservation, array $breakdown, string $invoiceNumber): array
    {
        $customer     = $reservation->dog?->customer;
        $vatPercentage = $breakdown['vat_percentage'] ?? Preference::get('vat_percentage', 20);

        // ── Company block ───────────────────────────────────────────────────
        $user               = Auth::user();
        $companyPictureBase64 = null;
        if ($user && ! empty($user->picture) && $user->picture !== 'no-user-picture.gif') {
            $picturePath = public_path('uploads/users/' . $user->picture);
            if (file_exists($picturePath)) {
                try {
                    $imageData = file_get_contents($picturePath);
                    $imageInfo = getimagesize($picturePath);
                    if ($imageInfo !== false) {
                        $companyPictureBase64 = 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageData);
                    }
                } catch (\Exception $e) {
                }
            }
        }

        // ── Customer block ──────────────────────────────────────────────────
        $customerFullName = trim(($customer?->title ?? '') . ' ' . ($customer?->name ?? ''));
        $customerAddress  = '';
        if (!empty($customer?->street) || !empty($customer?->zipcode) || !empty($customer?->city)) {
            $customerAddress = trim(($customer?->street ?? '') . ', ' . ($customer?->zipcode ?? '') . ' ' . ($customer?->city ?? ''));
        }

        // ── Payment method label ────────────────────────────────────────────
        $paymentMethods = collect($breakdown['checkout_entry_methods'] ?? [])
            ->unique()
            ->map(fn ($m) => $this->formatPaymentMethod($m))
            ->implode(' / ');
        if (empty($paymentMethods)) {
            $paymentMethods = 'Bar';
        }

        // ── Service line items ──────────────────────────────────────────────
        $dogName    = $reservation->dog?->name ?? 'Hund';
        $checkin    = Carbon::parse($breakdown['checkin_date'] ?? $reservation->checkin_date)->format('d.m');
        $checkout   = Carbon::parse($breakdown['actual_checkout_date'] ?? $reservation->checkout_date)->format('d.m.Y');
        $period     = $checkin . ' – ' . $checkout;
        $days       = (int) ($breakdown['days'] ?? 1);
        $isFlatRate = (bool) ($breakdown['is_flat_rate'] ?? false);
        $planTitle  = html_entity_decode($breakdown['plan_title'] ?? 'Hundepension', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Plan line: unit price is the gross per-unit; gross is the full plan gross before discount
        $planUnitGross  = (float) ($breakdown['plan_unit_gross'] ?? 0);
        $planTotalGross = (float) ($breakdown['plan_gross_before_discount'] ?? $planUnitGross);

        $planDescription = $planTitle . ' (' . $dogName . ', ' . $period . ')';

        $serviceItems = [];

        $planVatData = VATCalculator::calculate($planTotalGross, $vatPercentage);
        $planUnitVatData = VATCalculator::calculate($planUnitGross, $vatPercentage);
        $serviceItems[] = [
            'description'  => $planDescription,
            'quantity'     => $isFlatRate ? 1 : $days,
            'unit_price'   => $planUnitVatData['net'],
            'vat_amount'   => round($planVatData['vat'], 2),
            'gross'        => $planTotalGross,
            'is_plan'      => true,
            'is_flat_rate' => $isFlatRate,
        ];

        foreach ($breakdown['additional_costs'] ?? [] as $addCost) {
            $qty      = (int) ($addCost['quantity'] ?? 1);
            $addGross = (float) ($addCost['gross'] ?? 0);
            $addVatData = VATCalculator::calculate($addGross, $vatPercentage);
            $addUnitNet = round($qty > 0 ? $addVatData['net'] / $qty : $addVatData['net'], 2);
            $serviceItems[] = [
                'description'  => $addCost['title'] ?? 'Zusatzkosten',
                'quantity'     => $qty,
                'unit_price'   => $addUnitNet,
                'vat_amount'   => round($addVatData['vat'], 2),
                'gross'        => $addGross,
                'is_plan'      => false,
                'is_flat_rate' => false,
            ];
        }

        // ── VAT on final gross total ────────────────────────────────────────
        $grossTotal    = (float) ($breakdown['gross_total'] ?? 0);
        $vatData       = VATCalculator::calculate($grossTotal, $vatPercentage);
        $netTotal      = $vatData['net'];
        $vatAmount     = $vatData['vat'];

        return [
            'invoice_number'     => $invoiceNumber,
            'invoice_date'       => Carbon::now()->format('d.m.Y H:i:s'),
            'payment_method'     => $paymentMethods,
            'is_early_checkout'  => (bool) ($breakdown['is_early_checkout'] ?? false),
            'company' => [
                'picture_base64' => $companyPictureBase64,
                'name'           => $user->company_name ?? '',
                'address'        => $this->formatUserAddress($user),
                'phone'          => $user->phone ?? '',
                'email'          => $user->company_email ?? '',
                'iban'           => $user->iban ?? '',
                'bic'            => $user->bic ?? '',
            ],
            'customer' => [
                'type'    => $customer?->type ?? 'Stammkunde',
                'name'    => $customerFullName,
                'address' => $customerAddress,
                'country' => $customer?->land ?? 'AT',
            ],
            'service_items'      => $serviceItems,
            'discount_percentage' => (int) ($breakdown['discount_percentage'] ?? 0),
            'gross_total'        => $grossTotal,
            'advance_paid'       => (float) ($breakdown['advance_paid'] ?? 0),
            'balance_due'        => max(0.0, round($grossTotal - (float) ($breakdown['advance_paid'] ?? 0), 2)),
            'overpaid_amount'    => (float) ($breakdown['overpaid_amount'] ?? 0),
            'vat_percentage'     => $vatPercentage,
            'net_total'          => $netTotal,
            'vat_amount'         => $vatAmount,
        ];
    }

    /**
     * Cancel all existing invoices of $type for this reservation (keeping $newInvoice active).
     */
    private function invalidateInvoicesByType(Reservation $reservation, Invoice $newInvoice, string $type): void
    {
        Invoice::where('reservation_id', $reservation->id)
            ->where('id', '!=', $newInvoice->id)
            ->where('type', $type)
            ->update(['status' => 'cancelled']);
    }

    /**
     * Cancel all previous 'final' invoices for this reservation when a new Interne (final) invoice
     * is generated. Preserves: advance receipts, checkout (Schlussrechnung), and hellocash invoices.
     */
    private function invalidatePriorLocalInvoicesForReservation(Reservation $reservation, Invoice $newInvoice): void
    {
        $newType = strtolower((string) ($newInvoice->type ?? ''));
        if ($newType !== 'final' && $newType !== '') {
            return;
        }

        Invoice::where('reservation_id', $reservation->id)
            ->where('id', '!=', $newInvoice->id)
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhere('type', 'final');
            })
            ->update(['status' => 'cancelled']);
    }

    /**
     * Storno local advance (Anzahlung) PDFs for a reservation — used when total_due drops so receipts can be re-issued with updated stay dates.
     * Clears res_payment_entry_id on voided rows so a new invoice row can attach to the same payment entry.
     */
    public function voidLocalAdvanceInvoicesForReservation(Reservation $reservation): int
    {
        $query = Invoice::where('reservation_id', $reservation->id)
            ->where('type', 'advance')
            ->where('status', '!=', 'cancelled');

        if (Schema::hasColumn('invoices', 'hellocash_invoice_id')) {
            $query->whereNull('hellocash_invoice_id');
        }

        return (int) $query->update([
            'status' => 'cancelled',
            'res_payment_entry_id' => null,
        ]);
    }

    /**
     * When stay gross (total_due) decreases vs the value before sync, void advance PDFs and regenerate receipts:
     * each PDF line shows the allocated share of the new total_due (not the original payment amount).
     */
    public function refreshAdvanceInvoicesAfterStayTotalDropped(Reservation $reservation, float $previousTotalDue): void
    {
        $reservation->loadMissing('reservationPayment');
        $payment = $reservation->reservationPayment;
        if (! $payment) {
            return;
        }

        $newDue = (float) $payment->total_due;
        if ($previousTotalDue <= $newDue + 0.01) {
            return;
        }

        $this->voidLocalAdvanceInvoicesForReservation($reservation);

        $advanceEntries = ReservationPaymentEntry::query()
            ->where('res_payment_id', $payment->id)
            ->where('status', 'active')
            ->where('type', 'advance')
            ->orderBy('id')
            ->get();

        $remaining = max(0.0, round($newDue, 2));

        foreach ($advanceEntries as $entry) {
            $entryAmount = round((float) $entry->amount, 2);
            if ($entryAmount < 0.01) {
                continue;
            }

            $lineGross = round(min($entryAmount, $remaining), 2);
            $remaining = round(max(0.0, $remaining - $lineGross), 2);

            // Always re-issue a receipt per active advance after void: last lines may have 0 € stay share
            // (overpayment vs corrected total) with Hinweis in prepareSingleLineAdvanceReceiptData.
            $displayGross = $lineGross >= 0.01 ? $lineGross : 0.0;

            $resForPdf = Reservation::with(['dog.customer', 'plan', 'additionalCosts', 'reservationPayment.entries'])
                ->find($reservation->id);
            if (! $resForPdf) {
                continue;
            }

            $result = $this->generateInvoice($resForPdf, $entry, 'advance', $displayGross);
            if (! ($result['success'] ?? false)) {
                Log::warning('Regenerate advance invoice failed after stay change', [
                    'reservation_id' => $reservation->id,
                    'entry_id'       => $entry->id,
                    'error'          => $result['error'] ?? 'unknown',
                ]);
            }
        }
    }
}
