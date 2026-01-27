<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\HelloCashInvoice;
use App\Services\HelloCashService;
use App\Helpers\General;

class InvoicesController extends Controller
{
    protected HelloCashService $hellocashService;

    public function __construct(HelloCashService $hellocashService)
    {
        $this->hellocashService = $hellocashService;
    }

    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request)
    {
        if (!General::permissions('Rechnungen')) {
            return redirect()->route('admin.settings');
        }

        $query = HelloCashInvoice::with(['reservation.dog.customer', 'payment']);

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month') && $request->month !== 'all') {
            $query->whereMonth('created_at', $request->month);
        }

        if ($request->filled('invoice_type') && $request->invoice_type !== 'all') {
            $query->where('invoice_type', $request->invoice_type);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(30);
        $invoices->appends($request->query());

        $years = HelloCashInvoice::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('admin.invoices.index', compact('invoices', 'years'));
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
