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
        if (!General::permissions('Zahlung')) {
            return redirect()->route('admin.settings');
        }

        $query = HelloCashInvoice::with(['reservation.dog.customer', 'payment']);

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month') && $request->month !== 'all') {
            $query->whereMonth('created_at', $request->month);
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
        $invoice = HelloCashInvoice::findOrFail($id);

        if ($invoice->file_path && Storage::disk('local')->exists($invoice->file_path)) {
            return response()->stream(function () use ($invoice) {
                echo Storage::disk('local')->get($invoice->file_path);
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="invoice_' . $invoice->hellocash_invoice_id . '.pdf"',
            ]);
        }

        return $this->fetchAndServeInvoiceFromHelloCash($invoice);
    }

    /**
     * Download an invoice (force download)
     */
    public function download($id)
    {
        $invoice = HelloCashInvoice::findOrFail($id);

        if ($invoice->file_path && Storage::disk('local')->exists($invoice->file_path)) {
            return Storage::disk('local')->download(
                $invoice->file_path,
                'invoice_' . $invoice->hellocash_invoice_id . '.pdf',
                ['Content-Type' => 'application/pdf']
            );
        }

        return $this->fetchAndServeInvoiceFromHelloCash($invoice, true);
    }

    /**
     * Fetch invoice PDF from HelloCash API, store locally, and serve it
     */
    private function fetchAndServeInvoiceFromHelloCash(HelloCashInvoice $invoice, bool $download = false)
    {
        try {
            $result = $this->hellocashService->getInvoicePdf(
                $invoice->hellocash_invoice_id,
                'de_AT',
                false
            );

            if (!$result['success'] || empty($result['pdf_base64'])) {
                Log::error('HelloCash invoice fetch failed', [
                    'invoice_id' => $invoice->id,
                    'hellocash_invoice_id' => $invoice->hellocash_invoice_id,
                    'response' => $result,
                ]);

                abort(500, 'Rechnung konnte nicht von der Registrierkasse abgerufen werden.');
            }

            $pdfContent = base64_decode($result['pdf_base64'], true);

            if ($pdfContent === false) {
                Log::error('Invalid base64 PDF from HelloCash', [
                    'invoice_id' => $invoice->id,
                ]);

                abort(500, 'Ungültiges Rechnungsformat von der Registrierkasse.');
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
                Log::warning('Invoice saved failed (non-blocking)', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($download ? 'attachment' : 'inline')
                    . '; filename="invoice_' . $invoice->hellocash_invoice_id . '.pdf"',
            ];

            return response()->stream(function () use ($pdfContent) {
                echo $pdfContent;
            }, 200, $headers);

        } catch (\Throwable $e) {
            Log::error('Exception while fetching HelloCash invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Fehler beim Abrufen der Rechnung von der Registrierkasse.');
        }
    }
}
