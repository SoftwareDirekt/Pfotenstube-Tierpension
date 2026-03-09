@extends('admin.layouts.app')

@section('title')
    <title>Rechnung neu generieren</title>
@endsection

@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rechnung neu generieren</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <div><strong>Rechnung:</strong> #{{ $invoice->formatted_invoice_number }}</div>
                        <div><strong>Kunde:</strong> {{ $invoice->reservation?->dog?->customer?->name ?? 'N/A' }}</div>
                        <div><strong>Hund:</strong> {{ $invoice->reservation?->dog?->name ?? 'N/A' }}</div>
                    </div>

                    <form method="POST" action="{{ route('admin.invoices.regenerate', $invoice->id) }}">
                        @csrf
                        <div class="form-floating form-floating-outline mb-4">
                            <input
                                type="number"
                                min="1"
                                step="1"
                                class="form-control"
                                id="days"
                                name="days"
                                value="{{ old('days', (int)($invoice->payment->days ?? 1)) }}"
                                required
                            >
                            <label for="days">Tage</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4">
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control"
                                id="invoice_amount"
                                name="invoice_amount"
                                value="{{ old('invoice_amount', number_format((float)($invoice->payment->cost ?? 0), 2, '.', '')) }}"
                                required
                            >
                            <label for="invoice_amount">Rechnungsbetrag (Brutto)</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Neu generieren</button>
                            <a href="{{ route('admin.invoices') }}" class="btn btn-outline-secondary">Abbrechen</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

