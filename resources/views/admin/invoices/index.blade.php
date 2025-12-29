@extends('admin.layouts.app')
@section('title')
    <title>Rechnungen</title>
@endsection
@section('extra_css')
<style>
    .table-responsive {
        overflow-x: auto;
    }
</style>
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card">
            <div class="row">
                <div class="col-md-4 my-2">
                    <h5 class="card-header">Registrierkasse Rechnungen</h5>
                </div>
                <hr>
                <form class="row" method="GET" action="{{ route('admin.invoices') }}">
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline my-3">
                            <select name="month" class="form-control">
                                @foreach(range(1, 12) as $month)
                                    <option value="{{ $month }}" {{ request('month') == $month ? 'selected' : '' }}>
                                        {{ __('months.' . date("F", mktime(0, 0, 0, $month, 10))) }}
                                    </option>
                                @endforeach
                                <option value="all" {{ request('month') == 'all' || !request('month') ? 'selected' : '' }}>Alle</option>
                            </select>
                            <label for="month">Monat</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline my-3">
                            <select name="year" class="form-control">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                                <option value="all" {{ request('year') == 'all' || !request('year') ? 'selected' : '' }}>Alle</option>
                            </select>
                            <label for="year">Jahr</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating form-floating-outline my-4">
                            <button type="submit" class="btn btn-primary">Suchen</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table" id="myTable">
                    <thead class="table-light">
                        <tr>
                            <th>Rechnung #</th>
                            <th>Reservierung ID</th>
                            <th>Zahlung ID</th>
                            <th>Hund ID</th>
                            <th>Kunde</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @if(count($invoices) > 0)
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.invoices.view', $invoice->id) }}" 
                                           target="_blank" 
                                           class="text-primary text-decoration-none fw-bold">
                                            #{{ $invoice->hellocash_invoice_id }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($invoice->reservation)
                                            <a href="{{ route('admin.reservation', ['status' => ['all'], 'keyword' => $invoice->reservation->id, 'date_range' => '', 'per_page' => 30]) }}" 
                                               class="text-primary text-decoration-none">
                                                {{ $invoice->reservation->id }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->payment)
                                            <a href="{{ route('admin.payment', ['id' => $invoice->payment->id]) }}" 
                                               class="text-primary text-decoration-none">
                                                {{ $invoice->payment->id }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->reservation && $invoice->reservation->dog)
                                            {{ $invoice->reservation->dog->id ?? 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->reservation && $invoice->reservation->dog && $invoice->reservation->dog->customer)
                                            {{ $invoice->reservation->dog->customer->name ?? 'N/A' }} ({{ $invoice->reservation->dog->customer->id }})
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        {{ $invoice->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.invoices.download', $invoice->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-download"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted">Keine Rechnungen gefunden.</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

