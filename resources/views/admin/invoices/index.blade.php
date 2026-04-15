@extends('admin.layouts.app')
@section('title')
    <title>Rechnungen</title>
@endsection
@section('extra_css')
<style>
    .table-responsive {
        overflow-x: auto;
    }
    /* Gruppen-Rechnungen (Anzahlung/Checkout über reservation_groups) */
    tr.invoice-row-group {
        background-color: rgba(13, 110, 253, 0.09) !important;
    }
    tr.invoice-row-group td {
        border-color: rgba(13, 110, 253, 0.12);
    }
</style>
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card">
            <div class="row">
                <div class="col-md-4 my-2">
                    <h5 class="card-header">Rechnungen</h5>
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
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline my-3">
                            <select name="invoice_type" class="form-control">
                                <option value="all"        {{ request('invoice_type', 'all') === 'all'        ? 'selected' : '' }}>Alle Typen</option>
                                <option value="advance"    {{ request('invoice_type') === 'advance'    ? 'selected' : '' }}>Anzahlung</option>
                                <option value="checkout"   {{ request('invoice_type') === 'checkout'   ? 'selected' : '' }}>Schlussrechnung</option>
                                <option value="final"      {{ request('invoice_type') === 'final'      ? 'selected' : '' }}>Interne</option>
                                <option value="hellocash"  {{ request('invoice_type') === 'hellocash'  ? 'selected' : '' }}>Registrierkasse</option>
                            </select>
                            <label for="invoice_type">Rechnungstyp</label>
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
                            <th>Hund ID</th>
                            <th>Kunde</th>
                            <th>Typ</th>
                            <th>Erstellt am</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @if(count($invoices) > 0)
                            @foreach($invoices as $invoice)
                                @php
                                    $isGroupInvoice = (bool) $invoice->reservation_group_id;
                                    $group = $invoice->reservationGroup;
                                    $groupResIds = $group ? $group->reservations->sortBy('id')->pluck('id')->values() : collect();
                                    $groupDogIds = $group ? $group->reservations->pluck('dog_id')->filter()->unique()->sort()->values() : collect();
                                @endphp
                                <tr class="{{ $isGroupInvoice ? 'invoice-row-group' : '' }}">
                                    <td>
                                        <a href="{{ route('admin.invoices.view', $invoice->id) }}" 
                                           target="_blank" 
                                           class="text-primary text-decoration-none fw-bold">
                                            #{{ $invoice->formatted_invoice_number }}
                                        </a>
                                        @if($isGroupInvoice)
                                            <span class="badge bg-label-primary ms-1" title="Gruppenrechnung">G-{{ $invoice->reservation_group_id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->reservation)
                                            <a href="{{ route('admin.reservation', ['status' => ['all'], 'keyword' => $invoice->reservation->id, 'date_range' => '', 'per_page' => 30]) }}" 
                                               class="text-primary text-decoration-none">
                                                {{ $invoice->reservation->id }}
                                            </a>
                                        @elseif($groupResIds->isNotEmpty())
                                            @foreach($groupResIds as $idx => $rid)
                                                @if($idx > 0)<span class="text-muted">, </span>@endif
                                                <a href="{{ route('admin.reservation', ['status' => ['all'], 'keyword' => $rid, 'date_range' => '', 'per_page' => 30]) }}"
                                                   class="text-primary text-decoration-none">{{ $rid }}</a>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->reservation?->dog)
                                            {{ $invoice->reservation->dog->id }}
                                        @elseif($groupDogIds->isNotEmpty())
                                            {{ $groupDogIds->implode(', ') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->reservation?->dog?->customer)
                                            {{ $invoice->reservation->dog->customer->name }} ({{ $invoice->reservation->dog->customer->id }})
                                        @elseif($invoice->customer)
                                            {{ $invoice->customer->name }} ({{ $invoice->customer->id }})
                                        @elseif($group?->customer)
                                            {{ $group->customer->name }} ({{ $group->customer->id }})
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->type === 'hellocash')
                                            Registrierkasse
                                        @elseif($invoice->type === 'advance')
                                            Anzahlung
                                        @elseif($invoice->type === 'checkout')
                                            Schlussrechnung
                                        @elseif($invoice->type === 'final')
                                            Interne
                                        @else
                                            Lokal
                                        @endif
                                    </td>
                                    <td>
                                        {{ $invoice->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td>
                                        @if(($invoice->status ?? 'paid') === 'cancelled')
                                            <span class="badge bg-danger">Storniert</span>
                                        @else
                                            <span class="badge bg-success">Bezahlt</span>
                                        @endif
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
                                <td colspan="8" class="text-center py-4">
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

