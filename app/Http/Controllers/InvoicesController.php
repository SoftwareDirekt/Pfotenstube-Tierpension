<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\HelloCashInvoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\HelloCashService;
use App\Services\BankInvoiceService;
use App\Helpers\General;

class InvoicesController extends Controller
{
    protected HelloCashService $hellocashService;
    protected BankInvoiceService $bankInvoiceService;

    public function __construct(HelloCashService $hellocashService, BankInvoiceService $bankInvoiceService)
    {
        $this->hellocashService = $hellocashService;
        $this->bankInvoiceService = $bankInvoiceService;
    }

    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request)
    {
        if (!General::permissions('Rechnungen')) {
            return redirect()->route('admin.settings');
        }

        $supportsGroupedInvoices = Schema::hasColumn('hellocash_invoices', 'is_grouped')
            && Schema::hasColumn('hellocash_invoices', 'reservation_ids')
            && Schema::hasColumn('hellocash_invoices', 'customer_id')
            && Schema::hasColumn('payments', 'invoice_id');

        $query = HelloCashInvoice::with([
            'reservation.dog.customer',
            'payment',
        ]);

        if ($supportsGroupedInvoices) {
            $query->with([
                'customer',
                'payments.reservation.dog',
            ]);
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month') && $request->month !== 'all') {
            $query->whereMonth('created_at', $request->month);
        }

        if (
            Schema::hasColumn('hellocash_invoices', 'invoice_type')
            && $request->filled('invoice_type')
            && $request->invoice_type !== 'all'
        ) {
            $query->where('invoice_type', $request->invoice_type);
        }

        // Sort by invoice number descending for local invoices, by created_at for cashier
        $invoices = $query->orderByDesc('created_at')->paginate(30);
        $invoices->appends($request->query());

        $years = HelloCashInvoice::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('admin.invoices.index', compact('invoices', 'years', 'supportsGroupedInvoices'));
    }

    /**
     * Show the regenerate form for a local invoice.
     */
    public function regenerateForm($id)
    {
        if (!General::permissions('Rechnungen')) {
            return redirect()->route('admin.settings');
        }

        $invoice = HelloCashInvoice::with(['reservation.dog.customer', 'payment'])->findOrFail($id);

        if (($invoice->invoice_type ?? null) !== 'local') {
            return redirect()->route('admin.invoices')->with('error', 'Nur lokale Rechnungen können neu generiert werden.');
        }

        if (($invoice->is_grouped ?? false) === true) {
            return redirect()->route('admin.invoices')->with('error', 'Gruppierte Rechnungen können derzeit nicht manuell neu generiert werden.');
        }

        if (!$invoice->payment || !$invoice->reservation) {
            return redirect()->route('admin.invoices')->with('error', 'Die Rechnung hat keine vollständige Zahlungs-/Reservierungsdatenbasis.');
        }

        return view('admin.invoices.regenerate', compact('invoice'));
    }

    /**
     * Regenerate an existing local invoice with manual days/amount.
     */
    public function regenerate(Request $request, $id)
    {
        if (!General::permissions('Rechnungen')) {
            return redirect()->route('admin.settings');
        }

        $request->validate([
            'days' => 'required|integer|min:1',
            'invoice_amount' => 'required|numeric|min:0',
        ]);

        $invoice = HelloCashInvoice::with(['reservation.dog.customer', 'payment'])->findOrFail($id);
        $payment = $invoice->payment;
        $reservation = $invoice->reservation;

        if (($invoice->invoice_type ?? null) !== 'local') {
            return back()->with('error', 'Nur lokale Rechnungen können neu generiert werden.');
        }

        if (($invoice->is_grouped ?? false) === true) {
            return back()->with('error', 'Gruppierte Rechnungen können derzeit nicht manuell neu generiert werden.');
        }

        if (!$payment instanceof Payment || !$reservation instanceof Reservation) {
            return back()->with('error', 'Die Rechnung hat keine vollständige Zahlungs-/Reservierungsdatenbasis.');
        }

        $result = $this->bankInvoiceService->regenerateLocalInvoice(
            $invoice,
            $reservation,
            $payment,
            [
                'days' => (int) $request->days,
                'invoice_amount' => round((float) $request->invoice_amount, 2),
            ]
        );

        if (!($result['success'] ?? false)) {
            return back()->withInput()->with('error', $result['error'] ?? 'Rechnung konnte nicht neu generiert werden.');
        }

        return redirect()->route('admin.invoices')->with('success', 'Rechnung wurde erfolgreich neu generiert.');
    }

    /**
     * View an invoice (inline PDF)
     */
    public function view($id)
    {
        if (!General::permissions('Rechnungen')) {
            return redirect()->route('admin.settings');
        }

        $invoice = HelloCashInvoice::findOrFail($id);

        if ($invoice->invoice_type === 'local') {
            if ($invoice->file_path) {
                $fullPath = storage_path('app/' . $invoice->file_path);
                if (file_exists($fullPath)) {
                    return response()->file($fullPath, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }
            }
            abort(404, 'Rechnungsdatei nicht gefunden.');
        }

        if ($invoice->invoice_type === 'cashier') {
            if ($invoice->file_path) {
                $fullPath = storage_path('app/' . $invoice->file_path);
                if (file_exists($fullPath)) {
                    return response()->file($fullPath, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }
            }
            
            // If file doesn't exist localy, try to fetch from HelloCash API
            return $this->fetchAndServeInvoiceFromHelloCash($invoice, false);
        }

        abort(404, 'Rechnungsdatei nicht gefunden.');
    }

    /**
     * Download an invoice (force download)
     */
    public function download($id)
    {
        if (!General::permissions('Rechnungen')) {
            return redirect()->route('admin.settings');
        }

        $invoice = HelloCashInvoice::findOrFail($id);

        if ($invoice->invoice_type === 'local') {
            if ($invoice->file_path) {
                $fullPath = storage_path('app/' . $invoice->file_path);
                if (file_exists($fullPath)) {
                    $filename = $this->getInvoiceFilename($invoice);
                    return response()->download(
                        $fullPath,
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }
            }
            abort(404, 'Rechnungsdatei nicht gefunden.');
        }

        if ($invoice->invoice_type === 'cashier') {
            if ($invoice->file_path) {
                $fullPath = storage_path('app/' . $invoice->file_path);
                if (file_exists($fullPath)) {
                    $filename = $this->getInvoiceFilename($invoice);
                    return response()->download(
                        $fullPath,
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }
            }
            
            // If file doesn't exist, try to fetch from HelloCash API
            return $this->fetchAndServeInvoiceFromHelloCash($invoice, true);
        }

        abort(404, 'Rechnungsdatei nicht gefunden.');
    }

    /**
     * Get appropriate filename for invoice download
     */
    private function getInvoiceFilename(HelloCashInvoice $invoice): string
    {
        // Use Invoice model's formatted invoice number accessor
        $formattedNumber = $invoice->formatted_invoice_number;
        
        // Replace 'N/A' with 'unknown' for filenames
        if ($formattedNumber === 'N/A') {
            $formattedNumber = 'unknown';
        }
        
        return 'invoice_' . str_replace('-', '_', $formattedNumber) . '.pdf';
    }

    /**
     * Fetch invoice PDF from HelloCash API, store locally, and serve it
     */
    private function fetchAndServeInvoiceFromHelloCash(HelloCashInvoice $invoice, bool $download = false) 
    {
        if (empty($invoice->hellocash_invoice_id)) {
            abort(400, 'Rechnungs-ID fehlt. Die Rechnung konnte nicht von der Registrierkasse abgerufen werden.');
        }

        try {
            $result = $this->hellocashService->getInvoicePdf(
                $invoice->hellocash_invoice_id,
                'de_AT',
                false
            );

            if (!$result['success'] || empty($result['pdf_base64'])) {
                Log::error('HelloCash invoice fetch failed', [
                    'invoice_id' => $invoice->id,
                    'response' => $result,
                ]);

                abort(500, 'Rechnung konnte nicht abgerufen werden.');
            }

            $pdfContent = base64_decode($result['pdf_base64'], true);

            if ($pdfContent === false) {
                abort(500, 'Ungültiges PDF-Format.');
            }

            // Save locally
            try {
                $year = $invoice->created_at->format('Y');
                $month = $invoice->created_at->format('m');
                $filename = 'invoice_' . $invoice->hellocash_invoice_id . '.pdf';
                $path = "invoices/{$year}/{$month}/{$filename}";

                Storage::disk('local')->put($path, $pdfContent);
                $invoice->update(['file_path' => $path]);
            } catch (\Throwable $e) {
                Log::warning('Invoice save failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $filename = $this->getInvoiceFilename($invoice);
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    ($download ? 'attachment' : 'inline')
                    . '; filename="' . $filename . '"',
            ]);

        } catch (\Throwable $e) {
            Log::error('HelloCash exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Fehler beim Abrufen der Rechnung.');
        }
    }
}
