<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\HelloCashInvoice;
use App\Helpers\General;

class InvoicesController extends Controller
{
    public function index(Request $request)
    {
        if (!General::permissions('Zahlung')) {
            return redirect()->route('admin.settings');
        }

        $query = HelloCashInvoice::with(['reservation.dog.customer', 'payment']);

        // Filter by year
        if ($request->filled('year') && $request->input('year') !== 'all') {
            $query->whereYear('created_at', $request->input('year'));
        }

        // Filter by month
        if ($request->filled('month') && $request->input('month') !== 'all') {
            $query->whereMonth('created_at', $request->input('month'));
        }

        $invoices = $query->orderByDesc('created_at')->paginate(30);
        $invoices->appends($request->query());

        $years = HelloCashInvoice::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('admin.invoices.index', compact('invoices', 'years'));
    }

    public function view($id)
    {
        $invoice = HelloCashInvoice::findOrFail($id);

        if (!$invoice->file_path || !Storage::disk('local')->exists($invoice->file_path)) {
            abort(404, 'Invoice file not found');
        }

        $file = Storage::disk('local')->get($invoice->file_path);
        return response($file, 200)->header('Content-Type', 'application/pdf');
    }

    public function download($id)
    {
        $invoice = HelloCashInvoice::findOrFail($id);

        if (!$invoice->file_path || !Storage::disk('local')->exists($invoice->file_path)) {
            abort(404, 'Invoice file not found');
        }

        return Storage::disk('local')->download($invoice->file_path, 'invoice_' . $invoice->hellocash_invoice_id . '.pdf');
    }
}

