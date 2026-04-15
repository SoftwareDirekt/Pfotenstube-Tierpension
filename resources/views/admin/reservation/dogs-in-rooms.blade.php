@extends('admin.layouts.app')
@section('title')
    <title>Mehrfachkasse – Gruppen</title>
@endsection
@section('extra_css')
<style>
    .bulk-customer-header td { vertical-align: middle; }
    .bulk-customer-header { border-top: 2px solid #e7eaf3; }
    .bulk-dog-row td:first-child { width: 2.5rem; }
</style>
@endsection
@section('body')
@php
    $reservationsList = collect($reservations ?? [])->filter(fn ($r) => isset($r->dog));
    $byCustomer = $reservationsList
        ->sortBy(fn ($r) => \Illuminate\Support\Str::lower($r->dog->customer->name ?? ''))
        ->groupBy(fn ($r) => $r->dog->customer_id);
@endphp
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">


        <div class="card">
            <form id="bulkCheckoutForm" action="{{route('admin.dogs.rooms.checkout-post')}}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-header mb-0">Mehrfachkasse</h5>
                    </div>
                    <div class="col-md-6 pt-2">
                        <div class="d-flex justify-content-end">
                            <div class="mx-3">
                                <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.dogs.in.rooms')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Suche">
                            </div>
                            <div class="checkout">
                                <button type="submit" class="btn btn-primary">Kasse</button>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="table-responsive text-nowrap">
                  <table class="table" id="myTable">
                    <thead class="table-light">
                      <tr>
                        <th style="width:2.75rem;">Auswahl</th>
                        <th>Zimmer Nummer</th>
                        <th>Hund ID</th>
                        <th>Hund Name</th>
                        <th>Kunde</th>
                        <th>Telefonnummer</th>
                        <th>Preisplan</th>
                        <th>Einchecken</th>
                        <th>Auschecken</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0" id="bulkCheckoutTbody">
                    @if($byCustomer->isNotEmpty())
                    @foreach($byCustomer as $customerId => $rows)
                        @php
                            $first = $rows->first();
                            $cust = $first->dog->customer;
                            $n = $rows->count();
                        @endphp
                      <tr class="bulk-customer-header table-secondary" data-customer-id="{{ $customerId }}">
                        <td class="align-middle">
                            <div class="form-check mb-0 ms-1">
                                <input type="checkbox" class="form-check-input customer-bulk-check" data-customer-id="{{ $customerId }}" id="cust_check_{{ $customerId }}" autocomplete="off">
                            </div>
                        </td>
                        <td colspan="8" class="align-middle py-3">
                            <strong>{{ $cust->name ?? 'Kunde' }}</strong>
                            @if(!empty($cust->id_number))
                                <span class="text-muted">({{ $cust->id_number }})</span>
                            @endif
                            <span class="text-muted ms-1">· {{ $n }} {{ $n === 1 ? 'Hund' : 'Hunde' }}</span>
                            @if($cust->phone ?? null)
                                <span class="text-muted ms-1">· Tel. {{ $cust->phone }}</span>
                            @endif
                        </td>
                      </tr>
                      @foreach($rows as $obj)
                        @php if(!isset($obj->dog)){ continue; } @endphp
                      <tr class="bulk-dog-row" data-customer-id="{{ $customerId }}">
                        <td class="bg-body-secondary border-0"></td>
                        <td>{{ $obj->room->number ?? '' }}</td>
                        <td>{{ $obj->dog->id }}</td>
                        <td>{{ $obj->dog->name }}</td>
                        <td>
                            <a href="{{ route('admin.customers.preview', ['id' => $obj->dog->customer->id]) }}">
                                {{ $obj->dog->customer->name }} ({{ $obj->dog->customer->id_number }})
                            </a>
                        </td>
                        <td>{{ $obj->dog->customer->phone }}</td>
                        <td>{{ $obj->plan->title ?? '' }}</td>
                        <td>{{ date('d.m.Y', strtotime($obj->checkin_date)) }}</td>
                        <td>{{ date('d.m.Y', strtotime($obj->checkout_date)) }}</td>
                      </tr>
                      @endforeach
                    @endforeach
                    @else
                      <tr>
                        <td colspan="9" class="text-center">No records found</td>
                      </tr>
                    @endif
                    </tbody>
                  </table>
                </div>
                <div id="bulk-entry-hidden-host" class="visually-hidden" aria-hidden="true">
                    @foreach($reservationsList as $obj)
                        @if(!isset($obj->dog)) @continue @endif
                        <input type="hidden" name="entry[]" value="{{ $obj->id }}" disabled class="entry-hidden" data-customer-id="{{ $obj->dog->customer_id }}">
                    @endforeach
                </div>
            </form>

        </div>
    </div>
</div>
@endsection
@section('extra_js')

<script>
    function bulkFormatDate(str) {
        if (!str) return '';
        var sp1 = String(str).split(' ');
        var date = sp1[0].split('-');
        return date[2] + '.' + date[1] + '.' + date[0];
    }

    function buildBulkGroupedTableBody(res) {
        var withDog = (res || []).filter(function (obj) { return obj.dog; });
        var groups = {};
        withDog.forEach(function (obj) {
            var cid = String(obj.dog.customer_id);
            if (!groups[cid]) groups[cid] = [];
            groups[cid].push(obj);
        });
        var cids = Object.keys(groups).sort(function (a, b) {
            var na = (groups[a][0].dog.customer.name || '').toLowerCase();
            var nb = (groups[b][0].dog.customer.name || '').toLowerCase();
            return na.localeCompare(nb);
        });
        var html = '';
        cids.forEach(function (cid) {
            var rows = groups[cid];
            var c = rows[0].dog.customer;
            var idNum = (c.id_number != null && c.id_number !== '') ? '(' + c.id_number + ')' : '';
            var phone = c.phone || '';
            var n = rows.length;
            html += '<tr class="bulk-customer-header table-secondary" data-customer-id="' + cid + '">';
            html += '<td class="align-middle"><div class="form-check mb-0 ms-1"><input type="checkbox" class="form-check-input customer-bulk-check" data-customer-id="' + cid + '" id="cust_check_' + cid + '" autocomplete="off"></div></td>';
            html += '<td colspan="8" class="align-middle py-3">';
            html += '<strong>' + (c.name || '') + '</strong> ';
            if (idNum) html += '<span class="text-muted">' + idNum + '</span> ';
            html += '<span class="text-muted ms-1">· ' + n + ' ' + (n === 1 ? 'Hund' : 'Hunde') + '</span>';
            if (phone) html += '<span class="text-muted ms-1">· Tel. ' + phone + '</span>';
            html += ' <a href="/admin/customers/' + c.id + '/preview" class="ms-2 small">Kundenakte</a>';
            html += '</td></tr>';
            rows.forEach(function (obj) {
                html += '<tr class="bulk-dog-row" data-customer-id="' + cid + '">';
                html += '<td class="bg-body-secondary border-0"></td>';
                html += '<td>' + (obj.room && obj.room.number ? obj.room.number : '') + '</td>';
                html += '<td>' + obj.dog.id + '</td>';
                html += '<td>' + obj.dog.name + '</td>';
                html += '<td><a href="/admin/customers/' + obj.dog.customer.id + '/preview">' + obj.dog.customer.name + ' ' + (obj.dog.customer.id_number || '') + '</a></td>';
                html += '<td>' + (obj.dog.customer.phone || '') + '</td>';
                html += '<td>' + (obj.plan && obj.plan.title ? obj.plan.title : '') + '</td>';
                html += '<td>' + bulkFormatDate(obj.checkin_date) + '</td>';
                html += '<td>' + bulkFormatDate(obj.checkout_date) + '</td>';
                html += '</tr>';
            });
        });
        return html;
    }

    function buildBulkHiddenEntries(res) {
        var withDog = (res || []).filter(function (obj) { return obj.dog; });
        var html = '';
        withDog.forEach(function (obj) {
            var cid = String(obj.dog.customer_id);
            html += '<input type="hidden" name="entry[]" value="' + obj.id + '" disabled class="entry-hidden" data-customer-id="' + cid + '">';
        });
        return html;
    }

    async function ajaxSearch(route, method, token, id, keyword)
    {

        $.ajax({
            url: route,
            method: method,
            data: {_token: token, keyword: keyword},
            success: function(res)
            {
                clearBulkCustomerSelection();
                if (res.length > 0)
                {
                    $("#bulkCheckoutTbody").html(buildBulkGroupedTableBody(res));
                    $("#bulk-entry-hidden-host").html(buildBulkHiddenEntries(res));
                }
                else{
                    $("#bulkCheckoutTbody").html('<tr><td colspan="9" class="text-center">No record(s) found</td></tr>');
                    $("#bulk-entry-hidden-host").html('');
                }
            }
        })
    }

    function clearBulkCustomerSelection() {
        bulkCheckoutLockedCustomerId = null;
        $('.customer-bulk-check').prop('checked', false).prop('disabled', false);
        $('.entry-hidden').prop('disabled', true);
    }

    function applyBulkCustomerSelection(customerId) {
        bulkCheckoutLockedCustomerId = customerId;
        $('.customer-bulk-check').each(function () {
            var cid = String($(this).data('customer-id'));
            if (cid !== String(customerId)) {
                $(this).prop('checked', false).prop('disabled', true);
            } else {
                $(this).prop('checked', true).prop('disabled', false);
            }
        });
        $('.entry-hidden').each(function () {
            var cid = String($(this).data('customer-id'));
            $(this).prop('disabled', cid !== String(customerId));
        });
    }

    function releaseBulkCustomerSelection() {
        clearBulkCustomerSelection();
    }

    var bulkCheckoutLockedCustomerId = null;

    $(document).on('change', '.customer-bulk-check', function () {
        var cid = String($(this).data('customer-id'));
        if ($(this).is(':checked')) {
            applyBulkCustomerSelection(cid);
        } else {
            releaseBulkCustomerSelection();
        }
    });

    $("#bulkCheckoutForm").on('submit', function(e) {
        var n = $('.entry-hidden:not(:disabled)').length;
        if (n === 0) {
            e.preventDefault();
            alert('Bitte einen Kunden auswählen.');
            return false;
        }
    });

    $(window).on('load',function(){
        document.getElementById('togglerMenuBy').click();
    });

    $(function () {
        $('.entry-hidden').prop('disabled', true);
    });
</script>

@endsection
