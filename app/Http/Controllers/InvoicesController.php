<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
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

        $query = Invoice::with([
            'reservation.dog.customer',
            'paymentEntry',
            'customer',
            'reservationGroup.reservations.dog',
            'reservationGroup.customer',
            'groupEntry',
        ]);

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month') && $request->month !== 'all') {
            $query->whereMonth('created_at', $request->month);
        }

        if ($request->filled('invoice_type') && $request->invoice_type !== 'all') {
            $typeMap = [
                'advance'   => 'advance',
                'final'     => 'final',
                'checkout'  => 'checkout',
                'hellocash' => 'hellocash',
            ];
            if (isset($typeMap[$request->invoice_type])) {
                $query->where('type', $typeMap[$request->invoice_type]);
            }
        }

        $invoices = $query->orderByDesc('created_at')->paginate(30);
        $invoices->appends($request->query());

        $years = Invoice::selectRaw('YEAR(created_at) as year')
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

        $invoice = Invoice::findOrFail($id);

        return $this->serveInvoiceFile($invoice, false);
    }

    /**
     * Download an invoice (force download)
     */
    public function download($id)
    {
        if (!General::permissions('Rechnungen')) {
            return redirect()->route('admin.settings');
        }

        $invoice = Invoice::findOrFail($id);

        return $this->serveInvoiceFile($invoice, true);
    }

    /**
     * Get appropriate filename for invoice download
     */
    private function getInvoiceFilename(Invoice $invoice): string
    {
        // Use Invoice model's formatted invoice number accessor
        $formattedNumber = $invoice->formatted_invoice_number;
        
        // Replace 'N/A' with 'unknown' for filenames
        if ($formattedNumber === 'N/A') {
            $formattedNumber = 'unknown';
        }
        
        return 'invoice_' . str_replace('-', '_', $formattedNumber) . '.pdf';
    }

    private function serveInvoiceFile(Invoice $invoice, bool $download)
    {
        if ($invoice->file_path) {
            $fullPath = storage_path('app/' . $invoice->file_path);
            if (file_exists($fullPath)) {
                $filename = $this->getInvoiceFilename($invoice);
                if ($download) {
                    return response()->download($fullPath, $filename, ['Content-Type' => 'application/pdf']);
                }
                return response()->file($fullPath, ['Content-Type' => 'application/pdf']);
            }
        }

        if ($invoice->type === 'hellocash') {
            $hellocashInvoiceId = $this->getHelloCashInvoiceId($invoice);
            if (!$hellocashInvoiceId) {
                abort(404, 'Rechnungsdatei nicht gefunden.');
            }

            try {
                $result = $this->hellocashService->getInvoicePdf(
                    $hellocashInvoiceId,
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

                $year = $invoice->created_at->format('Y');
                $month = $invoice->created_at->format('m');
                $filename = 'invoice_hc_' . $hellocashInvoiceId . '.pdf';
                $path = "invoices/{$year}/{$month}/{$filename}";

                Storage::disk('local')->put($path, $pdfContent);
                $invoice->update(['file_path' => $path]);

                $downloadName = $this->getInvoiceFilename($invoice);
                return response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' =>
                        ($download ? 'attachment' : 'inline')
                        . '; filename="' . $downloadName . '"',
                ]);
            } catch (\Throwable $e) {
                Log::error('HelloCash exception', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);

                abort(500, 'Fehler beim Abrufen der Rechnung.');
            }
        }

        abort(404, 'Rechnungsdatei nicht gefunden.');
    }

    private function getHelloCashInvoiceId(Invoice $invoice): ?int
    {
        if (!empty($invoice->hellocash_invoice_id)) {
            return (int) $invoice->hellocash_invoice_id;
        }

        $number = $invoice->invoice_number ?? '';
        if (strpos($number, 'HC-') !== 0) {
            return null;
        }

        $id = substr($number, 3);
        if (!is_numeric($id)) {
            return null;
        }

        return (int)$id;
    }
}
