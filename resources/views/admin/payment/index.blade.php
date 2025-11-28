@extends('admin.layouts.app')
@section('title')
    <title>Zahlung</title>
@endsection
@section('extra_css')
<style>
    .table-responsive {
        overflow-x: auto;
    }

    #myTable th,
    #myTable td {
        white-space: nowrap;
    }
</style>
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card">
            <div class="row">
                <div class="col-md-4 my-2">
                    <h5 class="card-header">Zahlung Management</h5>
                </div>
                <hr>
                <form class="row">
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline my-3">
                            @php
                            if (!isset($_GET['month'])){
                                $_GET['month'] = 'all';
                            }
                            @endphp
                            <select name="month" class="form-control">
                                @foreach(range(1, 12) as $month)
                                    <option value="{{ $month }}" {{isset($_GET['month']) && $_GET['month'] == $month ? 'selected' : ''}}>{{ __('months.' . date("F", mktime(0, 0, 0, $month, 10))) }}</option>
                                @endforeach
                                <option value="all" {{isset($_GET['month']) && $_GET['month'] == 'all' ? 'selected' : ''}}>Alle</option>
                            </select>
                            <label for="type">Monat</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline my-3">
                            @php
                                if (!isset($_GET['year'])){
                                    $_GET['year'] = 'all';
                                }
                            @endphp
                            <select name="year" class="form-control">
                                @foreach(range(date('Y'), 2020) as $year)
                                    <option value="{{ $year }}" {{isset($_GET['year']) && $_GET['year'] == $year ? 'selected' : ''}}>{{ $year }}</option>
                                @endforeach
                                    <option value="all" {{isset($_GET['year']) && $_GET['year'] == 'all' ? 'selected' : ''}}>Alle</option>
                            </select>
                            <label for="type">Jahr</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating form-floating-outline my-4">
                            <button class="btn btn-primary">Search</button>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end">
                        <div class="form-floating form-floating-outline">
                            <form action="{{route('admin.payment')}}" method="GET" id="statusForm">
                                <select name="status" id="statusChoos" class="form-control mt-4" onchange="submitStatus(this.value)" style="width: 300px;border:2px solid blue;">
                                    <option value="alle" {{ isset($_GET['st']) && $_GET['st'] == 'alle' ? 'selected': '' }}>Alle</option>
                                    <option value="1" {{ isset($_GET['st']) && $_GET['st'] == 1 ? 'selected': '' }}>Bezahlt</option>
                                    <option value="0" {{ isset($_GET['st']) && $_GET['st'] == 0 ? 'selected': '' }}>Nicht bezahlt</option>
                                    <option value="2" {{ isset($_GET['st']) && $_GET['st'] == 2 ? 'selected': '' }}>Offen</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap">
              <table class="table" id="myTable">
                <thead class="table-light">
                  <tr>
                    <th>Reg #</th>
                    <th>Hund</th>
                    <th>Kunde</th>
                    <th>Einchecken</th>
                    <th>Auschecken</th>
                    <th>Art</th>
                    <th>Plan €</th>
                    <th>Extra €</th>
                    <th>Rabatt</th>
                    <th>R.Betrag €</th>
                    <th>Erh.Betrag €</th>
                    <th>Rest.Betrag €</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if(count($payments) > 0)
                    @foreach($payments as $obj)
                    @php
                        $planCost = isset($obj->plan_cost) ? (float)$obj->plan_cost : 0.0;
                        $specialCost = isset($obj->special_cost) ? (float)$obj->special_cost : 0.0;
                        $invoiceTotal = isset($obj->cost) ? (float)$obj->cost : 0.0;
                        $receivedAmount = isset($obj->received_amount) ? (float)$obj->received_amount : 0.0;
                        $discountPercent = isset($obj->discount) ? (float)$obj->discount : 0.0;
                        $discountAmount = isset($obj->discount_amount) ? (float)$obj->discount_amount : 0.0;
                        
                        // Use effective remaining amount (original - settlements received)
                        // Accessor handles loading settlements if needed, so no fallback needed
                        $originalRemaining = isset($obj->remaining_amount) ? (float)$obj->remaining_amount : ($invoiceTotal - $receivedAmount);
                        $effectiveRemaining = $obj->effective_remaining_amount; // Accessor always returns a value
                        $remainingClass = $effectiveRemaining > 0 ? 'text-danger' : 'text-success';
                        
                        // Check if related data exists (for deleted reservations/dogs/customers)
                        $hasReservation = isset($obj->reservation) && $obj->reservation != null;
                        $hasDog = $hasReservation && isset($obj->reservation->dog) && $obj->reservation->dog != null;
                        $hasCustomer = $hasDog && isset($obj->reservation->dog->customer) && $obj->reservation->dog->customer != null;
                    @endphp
                    <tr>
                        <td>{{ $obj->id }}</td>
                        <td>
                            @if($hasDog)
                                {{ $obj->reservation->dog->name }}
                            @else
                                <span class="text-muted" title="Reservierung oder Hund wurde gelöscht">Gelöscht</span>
                            @endif
                        </td>
                        <td>
                            @if($hasCustomer)
                                <a href="{{ route('admin.customers.preview', ['id'=> $obj->reservation->dog->customer_id]) }}">
                                    {{ $obj->reservation->dog->customer->name }}
                                </a>
                            @else
                                <span class="text-muted" title="Kunde wurde gelöscht">Gelöscht</span>
                            @endif
                        </td>
                        <td>
                            @if($hasReservation && isset($obj->reservation->checkin_date))
                                {{ date('d.m.Y', strtotime($obj->reservation->checkin_date)) }}
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($hasReservation && isset($obj->reservation->checkout_date))
                                {{ date('d.m.Y', strtotime($obj->reservation->checkout_date)) }}
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $obj->type }}</td>
                        <td>{{ number_format($planCost, 2) }}&euro;</td>
                        <td>{{ number_format($specialCost, 2) }}&euro;</td>
                        <td>{{ number_format($discountAmount, 2) }}&euro;</td>
                        <td>{{ number_format($invoiceTotal, 2) }}&euro;</td>
                        <td>{{ number_format($receivedAmount, 2) }}&euro;</td>
                        <td>
                            <span class="{{ $remainingClass }}">{{ number_format($effectiveRemaining, 2) }}&euro;</span>
                            @if($effectiveRemaining < $originalRemaining)
                                <small class="text-muted d-block" style="cursor: pointer;" onclick="showSettlementDetails({{ $obj->id }})" title="Klicken Sie, um die Details der Begleichung anzuzeigen">
                                    ({{ number_format($originalRemaining - $effectiveRemaining, 2) }}€ beglichen)
                                </small>
                            @endif
                        </td>
                        <td>
                            @php
                                // Determine status: if invoice is 0, it's paid; if effective remaining is 0, it's paid
                                $displayStatus = $obj->status;
                                if ($invoiceTotal < 0.01) {
                                    // Invoice amount is 0 (e.g., organization plan), automatically paid
                                    $displayStatus = 1;
                                } elseif ($effectiveRemaining < 0.01) {
                                    // Effective remaining is 0 (fully settled), automatically paid
                                    $displayStatus = 1;
                                }
                            @endphp
                            @if($displayStatus == 0)
                            <span class="badge bg-danger me-1">Nicht bezahlt</span>
                            @elseif($displayStatus == 1)
                            <span class="badge bg-success me-1">Bezahlt</span>
                            @elseif($displayStatus == 2)
                            <span class="badge bg-info me-1">Offen</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
              </table>
            </div>
            <div class="paginate">
                {{$payments->links()}}
            </div>
        </div>
    </div>
</div>

{{-- Settlement Details Modal --}}
<div class="modal fade" id="settlementDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-3">
                <h4 class="modal-title fw-bold m-0 p-0">
                    <i class="bx bx-receipt me-2"></i>Begleichungsdetails
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f5f5f9;">
                <div id="settlementDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Laden...</span>
                        </div>
                        <p class="mt-3 text-muted">Lade Begleichungsdetails...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Schließen
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('extra_js')

<script>

    $(window).on('load',function(){
        document.getElementById('togglerMenuBy').click();
    });

    async function ajaxSearch(route, method, token, id, keyword)
    {
        $.ajax({
            url: route,
            method: method,
            data: {_token: token, keyword: keyword},
            success: function(res)
            {
                let html = '';
                if(res.length > 0)
                {
                    res.forEach(obj => {
                        html += '<tr>';
                        html += `<td>${obj.id}</td>`;
                        html += `<td>${obj.name}</td>`;
                        html += `<td>${obj.email}</td>`;
                        html += `<td>${obj.username}</td>`;
                        html += `<td>${(obj.phone != null) ? obj.phone: ''}</td>`;
                        html += `<td>${(obj.address != null) ? obj.address: ''}</td>`;
                        html += `<td>${(obj.city != null) ? obj.city: ''}</td>`;
                        html += `<td>${ (obj.country != null) ? obj.country: '' }</td>`;
                        if(obj.status == 1)
                        {
                            html += '<td><span class="badge bg-label-primary me-1">Active</span></td>'
                        }else{
                            html += '<td><span class="badge bg-label-secondary me-1">Inactive</span></td>'
                        }
                        html += '<td><div class="d-flex justify-content-end">';
                        html += '<div><a href="#"><i class="fa fa-edit"></i></a></div>';
                        html += '<div class="mx-2"><button class="no-style actionBtn"><i class="fa fa-trash"></i></button></div>';
                        html += '</div></td>';
                        html += '</tr>';
                    });
                    $("#myTable tbody").html(html);
                }
            }
        })
    }

</script>

<script>
    $(document).ready(function() {
        $('#myTable').DataTable({
            order: [[0, 'desc']],
            language: {
                search: "Suche:",
                emptyTable: "Keine Zahlungen gefunden",
                paginate: {
                    previous: 'Zurück',
                    next: 'Weiter'
                }
            },
            pageLength: 50,
            lengthMenu: [
                [100, 200, 500, 1000, -1],
                [100, 200, 500, 1000, 'Alle']
            ],
            dom: '<"row"<"col-md-6"B><"col-md-6"f>>rtip',
            buttons: [
                {
                    extend: 'excel',
                    text: "Excel",
                    exportOptions: {
                        columns: [0,1,2,3,4,5,6,7,8,9,10,11]
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    exportOptions: {
                        columns: [0,1,2,3,4,5,6,7,8,9,10,11]
                    }
                },
                {
                    extend: 'print',
                    text: 'Drucken',
                    exportOptions: {
                        columns: [0,1,2,3,4,5,6,7,8,9,10,11]
                    }
                }
            ]
        });
    });

    function submitStatus(val)
    {
        window.location.href="/admin/payment?st="+val;
    }

    function showSettlementDetails(paymentId) {
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('settlementDetailsModal'));
        modal.show();
        
        // Reset content
        $('#settlementDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Laden...</span></div><p class="mt-3 text-muted">Lade Begleichungsdetails...</p></div>');
        
        // Fetch settlement details
        $.ajax({
            url: "{{ route('admin.payment.settlement.details', ['id' => ':id']) }}".replace(':id', paymentId),
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            success: function(data) {
                var html = '<div class="row g-4">';
                
                // Payment Information Card
                html += '<div class="col-12">';
                html += '<div class="card shadow-sm border-0">';
                html += '<div class="card-header bg-white border-bottom py-3">';
                html += '<h5 class="mb-0 fw-bold text-primary"><i class="bx bx-info-circle me-2"></i>Zahlungsinformationen</h5>';
                html += '</div>';
                html += '<div class="card-body p-4">';
                html += '<div class="row g-3">';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Zahlungs-ID</small><strong class="fs-5">#' + data.payment.id + '</strong></div></div></div>';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Hund</small><strong class="fs-6">' + data.payment.dog_name + '</strong></div></div></div>';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Kunde</small><strong class="fs-6">' + data.payment.customer_name + '</strong></div></div></div>';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Erstellt am</small><strong class="fs-6">' + data.payment.created_at + '</strong></div></div></div>';
                html += '<div class="col-md-4"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Rechnungsbetrag</small><strong class="fs-5 text-dark">' + data.payment.invoice_total + '€</strong></div></div></div>';
                html += '<div class="col-md-4"><div class="d-flex align-items-center p-3 bg-light border border-danger rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Ursprünglicher Restbetrag</small><strong class="fs-5 text-danger">' + data.payment.original_remaining + '€</strong></div></div></div>';
                html += '<div class="col-md-4"><div class="d-flex align-items-center p-3 bg-light ' + (parseFloat(data.payment.effective_remaining) > 0 ? 'border border-danger' : 'border border-success') + ' rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Aktueller Restbetrag</small><strong class="fs-5 ' + (parseFloat(data.payment.effective_remaining) > 0 ? 'text-danger' : 'text-success') + '">' + data.payment.effective_remaining + '€</strong></div></div></div>';
                html += '<div class="col-12"><div class="d-flex align-items-center p-3 bg-light border border-success rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Gesamt beglichen</small><strong class="fs-4 text-success">' + data.payment.total_settled + '€</strong></div></div></div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                
                if (data.settlement_trail && data.settlement_trail.length > 0) {
                    // Summary Card
                    var paymentIds = data.settlement_trail.map(s => '#' + s.settling_payment_id).join(', ');
                    html += '<div class="col-12">';
                    html += '<div class="alert alert-info border-0 shadow-sm mb-0">';
                    html += '<div class="d-flex align-items-start">';
                    html += '<i class="bx bx-info-circle fs-4 me-3 mt-1"></i>';
                    html += '<div class="flex-grow-1">';
                    html += '<h6 class="alert-heading mb-2 fw-bold">Zusammenfassung</h6>';
                    html += '<p class="mb-0">Diese ursprüngliche Schuld von <strong class="text-danger">' + data.payment.original_remaining + '€</strong> wurde von <strong>' + data.settlement_trail.length + ' Zahlung(en)</strong> beglichen: <span class="badge bg-primary">' + paymentIds + '</span></p>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Settlement Trail Card
                    html += '<div class="col-12">';
                    html += '<div class="card shadow-sm border-0">';
                    html += '<div class="card-header bg-white border-bottom py-3">';
                    html += '<h5 class="mb-0 fw-bold text-primary"><i class="bx bx-list-ul me-2"></i>Alle Zahlungen, die diese Schuld beglichen haben</h5>';
                    html += '</div>';
                    html += '<div class="card-body p-0">';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-hover align-middle mb-0">';
                    html += '<thead class="table-light">';
                    html += '<tr>';
                    html += '<th class="text-center" style="width: 60px;">#</th>';
                    html += '<th style="min-width: 150px;">Datum</th>';
                    html += '<th style="min-width: 120px;">Zahlungs-ID</th>';
                    html += '<th style="min-width: 150px;">Hund</th>';
                    html += '<th style="min-width: 150px;">Kunde</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Rechnungsbetrag</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Betrag erhalten</th>';
                    html += '<th class="text-end" style="min-width: 150px;">Beglichen</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Restbetrag vorher</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Restbetrag nachher</th>';
                    html += '</tr>';
                    html += '</thead>';
                    html += '<tbody>';
                    
                    data.settlement_trail.forEach(function(settlement, index) {
                        var rowClass = index % 2 === 0 ? '' : 'table-light';
                        html += '<tr class="' + rowClass + '">';
                        html += '<td class="text-center"><span class="badge bg-secondary">' + (index + 1) + '</span></td>';
                        html += '<td><i class="bx bx-calendar me-1 text-muted"></i>' + settlement.settling_payment_date + '</td>';
                        html += '<td><span class="badge bg-primary">#' + settlement.settling_payment_id + '</span></td>';
                        html += '<td>' + settlement.dog_name + '</td>';
                        html += '<td>' + settlement.customer_name + '</td>';
                        html += '<td class="text-end"><strong>' + settlement.settling_payment_invoice + '€</strong></td>';
                        html += '<td class="text-end">' + settlement.settling_payment_received + '€</td>';
                        html += '<td class="text-end"><span class="badge bg-success fs-6">' + settlement.amount_settled + '€</span></td>';
                        html += '<td class="text-end"><span class="text-danger fw-bold">' + settlement.balance_before + '€</span></td>';
                        html += '<td class="text-end"><span class="badge ' + (parseFloat(settlement.balance_after) > 0 ? 'bg-danger' : 'bg-success') + ' fs-6">' + settlement.balance_after + '€</span></td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody>';
                    html += '<tfoot class="table-light fw-bold">';
                    html += '<tr>';
                    html += '<td colspan="7" class="text-end align-middle">Gesamt beglichen:</td>';
                    html += '<td class="text-end"><span class="badge bg-success fs-5">' + data.payment.total_settled + '€</span></td>';
                    html += '<td colspan="2"></td>';
                    html += '</tr>';
                    html += '</tfoot>';
                    html += '</table>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                } else {
                    html += '<div class="col-12">';
                    html += '<div class="alert alert-warning border-0 shadow-sm">';
                    html += '<div class="d-flex align-items-start">';
                    html += '<i class="bx bx-info-circle fs-4 me-3 mt-1"></i>';
                    html += '<div>';
                    html += '<h6 class="alert-heading mb-2 fw-bold">Keine Begleichungen gefunden</h6>';
                    html += '<p class="mb-0">Diese Zahlung wurde noch nicht von anderen Zahlungen beglichen.</p>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '</div>'; // Close row
                
                $('#settlementDetailsContent').html(html);
            },
            error: function(xhr) {
                var errorMsg = 'Fehler beim Laden der Begleichungsdetails.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                $('#settlementDetailsContent').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            }
        });
    }

</script>
@endsection
