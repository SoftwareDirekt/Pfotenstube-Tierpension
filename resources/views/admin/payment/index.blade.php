@extends('admin.layouts.app')

@section('title')
    <title>Kasse / Zahlungen</title>
@endsection

@section('extra_css')
<style>
@media (max-width: 767.98px) {
    .cash-header-row .card-header { padding-bottom: 0.5rem; }
    .cash-header-actions {
        flex-direction: column;
        width: 100%;
        gap: 0.5rem;
    }
    .cash-header-actions .search-wrap,
    .cash-header-actions .btn-wrap {
        width: calc(100% - 1.5rem) !important;
        margin-left: 0.75rem !important;
        margin-right: 0.75rem !important;
        padding: 0 !important;
    }
    .cash-header-actions .form-control { width: 100%; }
    .cash-header-actions .btn-wrap .btn { width: 100%; }
    .cash-header-actions .btn-wrap { margin-bottom: 1rem !important; }
}
@media (min-width: 768px) {
    .cash-header-actions .btn-wrap { width: auto; }
}
</style>
@endsection

@section('body')
<div class="px-4 flex-grow-1 container-p-y">

    {{-- Grouped reservations --}}
    @if(isset($groups) && $groups->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="mdi mdi-account-group me-2"></i>Gruppenreservierungen</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Gruppe</th>
                        <th>Kunde</th>
                        <th>Hunde</th>
                        <th>Zeitraum</th>
                        <th>Gesamtbetrag</th>
                        <th>Bezahlt</th>
                        <th>Restbetrag</th>
                        <th>Status</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                        @php
                            $gPaid    = (float) $group->activeEntries()->sum('amount');
                            $gDue     = (float) $group->total_due;
                            $gBalance = max(0, round($gDue - $gPaid, 2));
                            $gCredit  = round(max(0, $gPaid - $gDue), 2);
                            $dogNames = $group->reservations->map(fn($r) => $r->dog?->name ?? '?')->implode(', ');
                            $firstRes = $group->reservations->first();
                            $lastRes  = $group->reservations->sortByDesc('checkout_date')->first();
                        @endphp
                        <tr>
                            <td><span class="badge bg-label-primary">G-{{ $group->id }}</span></td>
                            <td>
                                @if($group->customer)
                                    <a href="{{ route('admin.customers.preview', $group->customer->id) }}">{{ $group->customer->name }}</a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span title="{{ $dogNames }}">{{ $group->reservations->count() }} Hunde</span>
                                <br><small class="text-muted">{{ Str::limit($dogNames, 40) }}</small>
                            </td>
                            <td>{{ $firstRes?->checkin_date?->format('d.m.Y') ?? '-' }} - {{ $lastRes?->checkout_date?->format('d.m.Y') ?? '-' }}</td>
                            <td class="fw-bold">{{ number_format($gDue, 2) }} &euro;</td>
                            <td class="text-success fw-bold">{{ number_format($gPaid, 2) }} &euro;</td>
                            <td class="{{ $gBalance > 0.01 ? 'text-danger fw-bold' : 'text-success fw-bold' }}">{{ number_format($gBalance, 2) }} &euro;</td>
                            <td>
                                @if($group->status === 'paid')
                                    <span class="badge bg-success">Bezahlt</span>
                                @elseif($group->status === 'partial')
                                    <span class="badge bg-warning text-dark">Teilweise</span>
                                @else
                                    <span class="badge bg-danger">Offen</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" title="Gruppendetails"
                                            onclick="showGroupPaymentDetails({{ $group->id }})">
                                        <i class="mdi mdi-eye-outline"></i>
                                    </button>
                                    @if($gBalance > 0.01)
                                        <button class="btn btn-sm btn-outline-primary" onclick="openGroupPaymentModal({{ $group->id }}, '{{ addslashes($dogNames) }}', {{ $gBalance }})">
                                            <i class="mdi mdi-cash-plus"></i> Zahlung
                                        </button>
                                    @elseif($gCredit > 0.01)
                                        <button class="btn btn-sm btn-outline-warning" onclick="showGroupPaymentDetails({{ $group->id }})">
                                            <i class="mdi mdi-text-box-search-outline me-1"></i> Details
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Individual (ungrouped) reservations --}}
    <div class="card">
        <div class="row cash-header-row">
            <div class="col-12 col-md-6">
                <h5 class="card-header">Kasse / Zahlungen</h5>
            </div>
            <div class="col-12 col-md-6 pt-2">
                <form method="GET" action="{{ route('admin.payment') }}" class="d-flex flex-md-row justify-content-md-end cash-header-actions">
                    <div class="search-wrap mx-md-2">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="Suche nach Hund oder Kunde">
                    </div>
                    <div class="btn-wrap pe-md-3">
                        <button type="submit" class="btn btn-primary">Suchen</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Hund</th>
                        <th>Kunde</th>
                        <th>Zeitraum</th>
                        <th>Gesamtbetrag</th>
                        <th>Bezahlt</th>
                        <th>Restbetrag</th>
                        <th>Status</th>
                        <th>Hinweis</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $reservation)
                    @php
                        $payment  = $reservation->reservationPayment;
                        $totalPaid = $payment ? $payment->entries()->where('status','active')->sum('amount') : 0;
                        $totalDueF = (float) ($payment?->total_due ?? 0);
                        $balance   = $payment ? max(0, $totalDueF - (float)$totalPaid) : 0;
                        $creditOver = round(max(0, (float)$totalPaid - $totalDueF), 2);
                        $cancelledCount = $payment ? $payment->entries()->where('status','cancelled')->count() : 0;
                        $payStatus = $payment?->status ?? 'unpaid';
                    @endphp
                        <tr>
                            <td>{{ $reservation->id }}</td>
                            <td>{{ $reservation->dog->name }}</td>
                            <td><a href="{{ route('admin.customers.preview', $reservation->dog->customer->id) }}">{{ $reservation->dog->customer->name }}</a></td>
                            <td>{{ $reservation->checkin_date->format('d.m.Y') }} - {{ $reservation->checkout_date->format('d.m.Y') }}</td>
                            <td class="fw-bold">{{ number_format((float)($payment?->total_due ?? 0), 2) }} &euro;</td>
                            <td class="text-success fw-bold">{{ number_format($totalPaid, 2) }} &euro;</td>
                            <td class="{{ $balance > 0.01 ? 'text-danger fw-bold' : 'text-success fw-bold' }}">
                                {{ number_format($balance, 2) }} &euro;
                            </td>
                            <td>
                                @if($payStatus === 'paid')
                                    <span class="badge bg-success">Bezahlt</span>
                                @elseif($payStatus === 'partial')
                                    <span class="badge bg-warning text-dark">Teilweise</span>
                                @else
                                    <span class="badge bg-danger">Offen</span>
                                @endif
                            </td>
                            <td>
                                @if($creditOver > 0.01)
                                    <span class="badge bg-danger text-white" title="Korrigierter Gesamtbetrag niedriger als bereits bezahlt">
                                        <i class="mdi mdi-cash-refund me-1"></i>Rückzahlung {{ number_format($creditOver, 2) }} &euro;
                                    </span>
                                @elseif($cancelledCount > 0)
                                    <span class="badge bg-label-danger" title="Enthält stornierte Buchungen">
                                        <i class="mdi mdi-alert-circle-outline me-1"></i>Stornierungen
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    @if($payment && ! ($creditOver > 0.01 && $balance <= 0.01))
                                        <button class="btn btn-sm btn-outline-secondary" title="Details anzeigen"
                                                onclick="showPaymentDetails({{ $reservation->id }})">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </button>
                                    @endif
                                    @if($balance > 0.01)
                                        <button class="btn btn-sm btn-outline-primary" onclick="openPaymentModal({{ $reservation->id }}, '{{ addslashes($reservation->dog->name) }}', {{ $balance }})">
                                            <i class="mdi mdi-cash-plus"></i> Zahlung
                                        </button>
                                    @elseif($creditOver > 0.01)
                                        <button type="button" class="btn btn-sm btn-outline-warning" title="Überzahlung / Rückzahlungshinweis"
                                                onclick="showPaymentDetails({{ $reservation->id }})">
                                            <i class="mdi mdi-text-box-search-outline me-1"></i> Zahlungsdetails
                                        </button>
                                    @else
                                        <span class="badge bg-label-success">
                                            <i class="mdi mdi-check-circle-outline me-1"></i> Bezahlt
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $reservations->links() }}
        </div>
    </div>
</div>

{{-- Group Payment Modal --}}
<div class="modal fade" id="groupPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('admin.payment.group.add') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="group_id" id="grp_modal_group_id">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-account-group me-2"></i>
                    Gruppenanzahlung: <span id="grp_modal_dog_names" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                            <div>
                                <small class="text-muted text-uppercase d-block mb-1">Offener Gruppenbetrag</small>
                                <h3 class="mb-0 text-primary fw-bold">&euro; <span id="grp_modal_remaining_display">0.00</span></h3>
                                <input type="hidden" id="grp_modal_remaining">
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="setGroupFullAmount()">
                                <i class="mdi mdi-check-all me-1"></i> Alles setzen
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <select name="method" id="grp_modal_method" class="form-control">
                                <option value="Bar">Bar</option>
                                <option value="Bank">Bankueberweisung</option>
                            </select>
                            <label for="grp_modal_method">Methode</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="number" name="amount" id="grp_modal_amount" step="0.01" class="form-control" placeholder="0.00">
                            <label for="grp_modal_amount">Betrag (&euro;)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <textarea name="note" class="form-control h-px-100" placeholder="Optional..." rows="3"></textarea>
                            <label>Notiz</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary" id="submitGroupPaymentBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <i class="mdi mdi-check-circle-outline me-1"></i> Zahlung speichern
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Payment Modal (individual) --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('admin.payment.add') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="res_id" id="modal_res_id">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-cash-register me-2"></i>
                    Zahlung erfassen: <span id="modal_dog_name" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                            <div>
                                <small class="text-muted text-uppercase d-block mb-1">Offener Betrag</small>
                                <h3 class="mb-0 text-primary fw-bold">&euro; <span id="modal_remaining_display">0.00</span></h3>
                                <input type="hidden" id="modal_remaining">
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="setFullAmount()">
                                <i class="mdi mdi-check-all me-1"></i> Alles setzen
                            </button>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <input type="hidden" name="type" value="advance">
                            <select id="modal_payment_type" class="form-control" disabled>
                                <option value="advance" selected>Anzahlung</option>
                            </select>
                            <label for="modal_payment_type">Zahlungstyp</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="single_payment_fields">
                            <div class="row g-3">
                                <div class="col-md-6" id="single_method_wrapper">
                                    <div class="form-floating form-floating-outline">
                                        <select name="method" id="modal_single_method" class="form-control">
                                            <option value="Bar">Bar</option>
                                            <option value="Bank">Bankueberweisung</option>
                                        </select>
                                        <label for="modal_single_method">Methode</label>
                                    </div>
                                </div>
                                <div class="col-md-6" id="single_amount_wrapper">
                                    <div class="form-floating form-floating-outline">
                                        <input type="number" name="amount" id="modal_amount" step="0.01" class="form-control" placeholder="0.00">
                                        <label for="modal_amount">Betrag (&euro;)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <textarea name="note" class="form-control h-px-100" placeholder="Optional..." rows="3"></textarea>
                            <label>Notiz</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary" id="submitPaymentBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <i class="mdi mdi-check-circle-outline me-1"></i> Zahlung speichern
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Payment Details Modal --}}
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-3">
                <h4 class="modal-title fw-bold m-0">
                    <i class="mdi mdi-receipt me-2"></i>Zahlungsdetails
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body p-4" style="background-color:#f5f5f9;">
                <div id="paymentDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
                            <span class="visually-hidden">Laden...</span>
                        </div>
                        <p class="mt-3 text-muted">Lade Zahlungsdetails...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="mdi mdi-close me-1"></i>Schließen
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function showPaymentDetails(reservationId) {
        var modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
        modal.show();

        $('#paymentDetailsContent').html(
            '<div class="text-center py-5">' +
            '<div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;"><span class="visually-hidden">Laden...</span></div>' +
            '<p class="mt-3 text-muted">Lade Zahlungsdetails...</p></div>'
        );

        $.ajax({
            url: "{{ route('admin.payment.details', ['id' => ':id']) }}".replace(':id', reservationId),
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(data) {
                var p = data.payment;
                var statusLabel = { paid: 'Bezahlt', partial: 'Teilweise', unpaid: 'Offen' }[p.status] || p.status;
                var statusClass = { paid: 'bg-success', partial: 'bg-warning text-dark', unpaid: 'bg-danger' }[p.status] || 'bg-secondary';

                var html = '<div class="row g-4">';

                // Summary card
                html += '<div class="col-12"><div class="card shadow-sm border-0">';
                html += '<div class="card-header bg-white border-bottom py-3">';
                html += '<h5 class="mb-0 fw-bold text-primary"><i class="mdi mdi-information-outline me-2"></i>Details zur Reservierung</h5>';
                html += '</div>';
                html += '<div class="card-body p-4"><div class="row g-3">';
                html += cell('Hund', p.dog_name);
                html += cell('Kunde', p.customer_name);
                html += cell('Zeitraum', p.checkin + ' – ' + p.checkout);
                html += cell('Preisplan', p.plan);
                html += amountCell('Gesamtbetrag (korrigiert)', p.invoice_total + '€', 'text-dark');
                html += amountCell('Bereits bezahlt', p.total_paid + '€', 'text-success');
                html += amountCell('Restbetrag', p.balance + '€', parseFloat(String(p.balance).replace(',', '.')) > 0.005 ? 'text-danger' : 'text-success');
                html += '<div class="col-md-3"><div class="d-flex align-items-center p-3 bg-light rounded">';
                html += '<div class="flex-grow-1"><small class="text-muted d-block mb-1">Status</small>';
                html += '<span class="badge ' + statusClass + ' fs-6">' + statusLabel + '</span></div></div></div>';
                html += '</div>';

                var creditRaw = parseFloat(p.credit_overpayment_raw != null ? p.credit_overpayment_raw : String(p.credit_overpayment || '0').replace(',', '.')) || 0;
                if (creditRaw > 0.009) {
                    html += '<div class="alert alert-danger py-3 mt-3 mb-0 border border-danger">';
                    html += '<div class="d-flex align-items-start gap-2">';
                    html += '<i class="mdi mdi-cash-refund fs-3"></i>';
                    html += '<div><strong>Korrigierter Leistungsbetrag</strong> (laut aktuellem Check-in / Aufenthalt): <span class="text-nowrap">' + p.invoice_total + ' €</span><br>';
                    html += 'Bereits bezahlt: <span class="text-nowrap">' + p.total_paid + ' €</span><br>';
                    html += '<span class="fw-bold">Rückzahlung an Kunde erforderlich: ca. ' + p.credit_overpayment + ' €</span><br>';
                    html += '<small class="text-muted">Anzahlungs-Rechnungen wurden bei Bedarf storniert und mit aktualisiertem Zeitraum neu erstellt.</small></div>';
                    html += '</div></div>';
                }

                if (p.has_cancelled_entries) {
                    html += '<div class="alert alert-warning py-2 mt-3 mb-0">';
                    html += '<i class="mdi mdi-alert-circle-outline me-2"></i>';
                    html += '<strong>Hinweis:</strong> Diese Zahlung enthält <strong>' + p.cancelled_entries_count + ' stornierte Buchung(en)</strong>. Details siehe Zahlungshistorie unten.';
                    html += '</div>';
                }

                html += '</div></div></div>';

                // Entries table
                if (data.entries && data.entries.length > 0) {
                    html += '<div class="col-12"><div class="card shadow-sm border-0">';
                    html += '<div class="card-header bg-white border-bottom py-3">';
                    html += '<h5 class="mb-0 fw-bold text-primary"><i class="mdi mdi-format-list-bulleted me-2"></i>Zahlungshistorie</h5>';
                    html += '</div>';
                    html += '<div class="card-body p-0"><div class="table-responsive">';
                    html += '<table class="table table-hover align-middle mb-0">';
                    html += '<thead class="table-light"><tr>';
                    html += '<th>Datum</th><th>Typ</th><th>Methode</th><th>Betrag</th><th>Rückgabe</th><th>Notiz</th><th>Rechnung</th><th>Status</th>';
                    html += '</tr></thead><tbody>';

                    data.entries.forEach(function(e) {
                        var rowClass = e.status === 'cancelled' ? 'table-danger text-muted' : '';
                        var badge    = e.status === 'cancelled'
                            ? '<span class="badge bg-danger">Storniert</span>'
                            : '<span class="badge bg-success">Aktiv</span>';
                        var typeLabel = e.type === 'advance' ? 'Anzahlung' : 'Restzahlung';
                        var cleanNote = (e.note || '').replace(/\(Split:.*?\)/g, '').replace(/\[Split:.*?\]/g, '').trim() || '—';
                        var invoiceCol = '<span class="text-muted">—</span>';
                        if (e.invoice_number) {
                            var invStatus = e.invoice_status || 'paid';
                            var invBadge = invStatus === 'cancelled'
                                ? '<span class="badge bg-danger ms-1">Storniert</span>'
                                : '<span class="badge bg-success ms-1">Bezahlt</span>';
                            invoiceCol = '<div class="d-flex flex-wrap align-items-center gap-1">' +
                                '<span class="fw-semibold text-body">' + e.invoice_number + '</span>' + invBadge + '</div>';
                        }

                        html += '<tr class="' + rowClass + '">';
                        html += '<td>' + e.date + '</td>';
                        html += '<td>' + typeLabel + '</td>';
                        html += '<td>' + e.method + '</td>';
                        html += '<td class="fw-bold">' + e.amount + '€</td>';
                        html += '<td class="text-danger fw-bold">' + (e.overpaid_amount ? e.overpaid_amount + '€' : '—') + '</td>';
                        html += '<td><small>' + cleanNote + '</small></td>';
                        html += '<td>' + invoiceCol + '</td>';
                        html += '<td>' + badge + '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div></div></div></div>';
                }

                html += '</div>';
                $('#paymentDetailsContent').html(html);
            },
            error: function(xhr) {
                var msg = 'Fehler beim Laden der Zahlungsdetails.';
                if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                $('#paymentDetailsContent').html('<div class="alert alert-danger">' + msg + '</div>');
            }
        });
    }

    function cell(label, value) {
        return '<div class="col-md-3"><div class="d-flex align-items-center p-3 bg-light rounded">' +
               '<div class="flex-grow-1"><small class="text-muted d-block mb-1">' + label + '</small>' +
               '<strong class="fs-6">' + (value || '—') + '</strong></div></div></div>';
    }

    function amountCell(label, value, colorClass) {
        return '<div class="col-md-3"><div class="d-flex align-items-center p-3 bg-light rounded">' +
               '<div class="flex-grow-1"><small class="text-muted d-block mb-1">' + label + '</small>' +
               '<strong class="fs-5 ' + colorClass + '">' + value + '</strong></div></div></div>';
    }
</script>

<script>
    let currentRemaining = 0;

    function openPaymentModal(id, dogName, remaining) {
        document.getElementById('modal_res_id').value = id;
        document.getElementById('modal_dog_name').innerText = dogName;
        document.getElementById('modal_remaining').value = remaining;
        document.getElementById('modal_remaining_display').innerText = remaining.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('modal_single_method').value = 'Bank';
        document.getElementById('modal_amount').value = remaining.toFixed(2);

        currentRemaining = remaining;
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    }

    function setFullAmount() {
        document.getElementById('modal_amount').value = currentRemaining.toFixed(2);
    }

    document.querySelector('#paymentModal form').addEventListener('submit', function(e) {
        let amount = parseFloat(document.getElementById('modal_amount').value || 0);

        if (amount <= 0) {
            e.preventDefault();
            alert('Bitte geben Sie einen Betrag ein.');
            return;
        }

        if (amount > (currentRemaining + 0.01)) {
            e.preventDefault();
            alert('Der Betrag darf den offenen Betrag (' + currentRemaining.toFixed(2) + ' EUR) nicht ueberschreiten.');
            return;
        }

        const btn = document.getElementById('submitPaymentBtn');
        btn.disabled = true;
        btn.querySelector('.spinner-border').classList.remove('d-none');
    });
</script>

<script>
    let groupRemaining = 0;

    function openGroupPaymentModal(groupId, dogNames, remaining) {
        document.getElementById('grp_modal_group_id').value = groupId;
        document.getElementById('grp_modal_dog_names').innerText = dogNames;
        document.getElementById('grp_modal_remaining').value = remaining;
        document.getElementById('grp_modal_remaining_display').innerText = remaining.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('grp_modal_amount').value = remaining.toFixed(2);
        groupRemaining = remaining;
        new bootstrap.Modal(document.getElementById('groupPaymentModal')).show();
    }

    function setGroupFullAmount() {
        document.getElementById('grp_modal_amount').value = groupRemaining.toFixed(2);
    }

    document.querySelector('#groupPaymentModal form').addEventListener('submit', function(e) {
        let amount = parseFloat(document.getElementById('grp_modal_amount').value || 0);
        if (amount <= 0) { e.preventDefault(); alert('Bitte geben Sie einen Betrag ein.'); return; }
        if (amount > (groupRemaining + 0.01)) { e.preventDefault(); alert('Der Betrag darf den offenen Gruppenbetrag (' + groupRemaining.toFixed(2) + ' EUR) nicht ueberschreiten.'); return; }
        const btn = document.getElementById('submitGroupPaymentBtn');
        btn.disabled = true;
        btn.querySelector('.spinner-border').classList.remove('d-none');
    });

    function showGroupPaymentDetails(groupId) {
        var modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
        modal.show();
        $('#paymentDetailsContent').html(
            '<div class="text-center py-5"><div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;"><span class="visually-hidden">Laden...</span></div><p class="mt-3 text-muted">Lade Zahlungsdetails...</p></div>'
        );

        $.ajax({
            url: "{{ route('admin.payment.group.details', ['id' => ':id']) }}".replace(':id', groupId),
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(data) {
                var p = data.payment;
                var statusLabel = { paid: 'Bezahlt', partial: 'Teilweise', unpaid: 'Offen' }[p.status] || p.status;
                var statusClass = { paid: 'bg-success', partial: 'bg-warning text-dark', unpaid: 'bg-danger' }[p.status] || 'bg-secondary';

                var html = '<div class="row g-4">';
                html += '<div class="col-12"><div class="card shadow-sm border-0">';
                html += '<div class="card-header bg-white border-bottom py-3"><h5 class="mb-0 fw-bold text-primary"><i class="mdi mdi-account-group me-2"></i>Gruppenreservierung G-' + (p.group_id || '') + ' (' + p.dog_count + ' Hunde)</h5></div>';
                html += '<div class="card-body p-4"><div class="row g-3">';
                html += cell('Hunde', p.dog_name);
                html += cell('Kunde', p.customer_name);
                html += cell('Zeitraum', p.checkin + ' – ' + p.checkout);
                html += cell('Preisplan', p.plan);
                html += amountCell('Gesamtbetrag', p.invoice_total + '€', 'text-dark');
                html += amountCell('Bereits bezahlt', p.total_paid + '€', 'text-success');
                html += amountCell('Restbetrag', p.balance + '€', parseFloat(String(p.balance).replace(',', '.')) > 0.005 ? 'text-danger' : 'text-success');
                html += '<div class="col-md-3"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Status</small><span class="badge ' + statusClass + ' fs-6">' + statusLabel + '</span></div></div></div>';
                html += '</div></div>';

                // Reservations table inside the summary card
                if (data.reservations && data.reservations.length > 0) {
                    html += '<div class="px-4 pb-4"><h6 class="fw-bold text-muted mb-2 mt-2"><i class="mdi mdi-dog-side me-1"></i>Enthaltene Reservierungen</h6>';
                    html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
                    html += '<thead class="table-light"><tr><th>Res-ID</th><th>Hund-ID</th><th>Hund</th><th>Plan</th><th>Check-in</th><th>Check-out</th><th>Betrag</th></tr></thead><tbody>';
                    data.reservations.forEach(function(r) {
                        html += '<tr><td>' + r.reservation_id + '</td><td>' + r.dog_id + '</td><td>' + r.dog_name + '</td><td>' + r.plan + '</td><td>' + r.checkin + '</td><td>' + r.checkout + '</td><td class="fw-bold">' + r.gross_total + '€</td></tr>';
                    });
                    html += '</tbody></table></div></div>';
                }

                html += '</div></div>';

                if (data.entries && data.entries.length > 0) {
                    html += '<div class="col-12"><div class="card shadow-sm border-0">';
                    html += '<div class="card-header bg-white border-bottom py-3"><h5 class="mb-0 fw-bold text-primary"><i class="mdi mdi-format-list-bulleted me-2"></i>Zahlungshistorie</h5></div>';
                    html += '<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">';
                    html += '<thead class="table-light"><tr><th>#</th><th>Datum</th><th>Typ</th><th>Methode</th><th>Betrag</th><th>Notiz</th><th>Rechnung</th><th>Status</th></tr></thead><tbody>';
                    data.entries.forEach(function(e) {
                        var rowClass = e.status === 'cancelled' ? 'table-danger text-muted' : '';
                        var badge = e.status === 'cancelled' ? '<span class="badge bg-danger">Storniert</span>' : '<span class="badge bg-success">Aktiv</span>';
                        var typeLabel = e.type === 'advance' ? 'Anzahlung' : 'Restzahlung';
                        var invoiceCol = e.invoice_number
                            ? '<span class="fw-semibold text-body">' + e.invoice_number + '</span>'
                            : '<span class="text-muted">—</span>';
                        html += '<tr class="' + rowClass + '"><td>' + (e.id || '-') + '</td><td>' + e.date + '</td><td>' + typeLabel + '</td><td>' + e.method + '</td><td class="fw-bold">' + e.amount + '€</td><td><small>' + (e.note || '—') + '</small></td><td>' + invoiceCol + '</td><td>' + badge + '</td></tr>';
                    });
                    html += '</tbody></table></div></div></div></div>';
                }
                html += '</div>';
                $('#paymentDetailsContent').html(html);
            },
            error: function(xhr) {
                var msg = 'Fehler beim Laden der Gruppendetails.';
                if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                $('#paymentDetailsContent').html('<div class="alert alert-danger">' + msg + '</div>');
            }
        });
    }
</script>
@endsection
