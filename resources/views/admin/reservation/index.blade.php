@extends('admin.layouts.app')
@section('title')
    <title>Reservierung</title>
@endsection
@section('extra_css')
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/bootstrap-select/bootstrap-select.css" />
    <style>
        .select2-container {
            min-width: 200px !important;
            width: 100% !important;
        }

        @media (max-width: 768px) {

            /* For mobile view */
            .table-responsive {
                overflow-x: auto;
            }

            .select2-container {
                width: 100% !important;
            }

            .form-control {
                width: 100%;
            }

            .d-flex-end {
                justify-content: flex-start;
            }

            .btn-responsive {
                width: 100%;
                margin-top: 10px;
            }
        }

        @media (min-width: 769px) {
            .d-flex-end {
                justify-content: flex-end;
            }

            .btn-responsive {
                width: auto;
            }
        }
    </style>
@endsection
@section('body')
    @php
        $status = false;
        if (isset($_GET['sl'])) {
            $status = explode(',', $_GET['sl']);
        }
    @endphp
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="row gy-4">
            <div class="card">
                <div class="row">
                    <div class="col-md-3">
                        <h5 class="card-header">Reservierung Management</h5>
                    </div>
                    <div class="col-md-9 pt-2">
                        <div class="d-flex d-flex-end flex-wrap">
                            <div class="mx-2 d-flex">
                                <select class="form-control select2" multiple id="statusInput">
                                    <option value="all" {{ $status && in_array('all', $status) ? 'selected' : '' }}>All
                                        Records</option>
                                    <option value="1" {{ $status && in_array('1', $status) ? 'selected' : '' }}>Im
                                        Zimmer</option>
                                    <option value="2" {{ $status && in_array('2', $status) ? 'selected' : '' }}>Kasse
                                    </option>
                                    <option value="3" {{ $status && in_array('3', $status) ? 'selected' : '' }}>
                                        Reserviert</option>
                                </select>
                                <button class="btn btn-primary mx-2 btn-responsive" type="submit"
                                    onclick="filterStatus()">Filter</button>
                            </div>
                            <div class="mx-2">
                                <input type="text" class="form-control" autofocus
                                    onkeyup="ajaxSearch('{{ route('admin.reservation') }}', 'GET', '{{ csrf_token() }}', 'myTable', this.value)"
                                    placeholder="Search">
                            </div>
                            <div>
                                <a href="{{ route('admin.reservation.add.view') }}" class="btn btn-primary btn-responsive">
                                    <i class="fa fa-plus"></i> &nbsp; Neuer Reservierung
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table" id="myTable">
                        <thead class="table-light">
                            <tr>
                                <th>Hund ID</th>
                                <th>Hund Name</th>
                                <th>Kunde</th>
                                <th>Telefonnummer</th>
                                <th>Preisplan</th>
                                <th>
                                    <button class="no-style btn-sort"
                                        onclick="sortCheckin('{{ route('admin.reservation') }}', 'GET', '{{ csrf_token() }}','myTable')">
                                        <i class="fa fa-sort"></i> Einchecken
                                    </button>
                                </th>
                                <th>Auschecken</th>
                                <th>Status</th>
                                <th class="text-end">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($reservations as $obj)
                                @php
                                    if (!isset($obj->dog)) {
                                        continue;
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $obj->dog->id }}</td>
                                    <td>{{ $obj->dog->name }}</td>
                                    <td>
                                        <a
                                            href="{{ route('admin.customers.preview', ['id' => $obj->dog->customer->id]) }}">
                                            {{ $obj->dog->customer->name }} ({{ $obj->dog->customer->id_number }})
                                        </a>
                                    </td>
                                    <td>{{ $obj->dog->customer->phone }}</td>
                                    <td>@php echo isset($obj->plan->title) ? $obj->plan->title : '' @endphp</td>
                                    <td>{{ $obj->checkin_date->format('d.m.Y') }}</td>
                                    <td>{{ date('d.m.Y', strtotime($obj->checkout_date)) }}</td>
                                    <td>
                                        @if ($obj->status == 1)
                                            <span class="badge bg-success">Im Zimmer</span>
                                        @elseif($obj->status == 2)
                                            <span class="badge bg-warning">Kasse</span>
                                        @elseif($obj->status == 3)
                                            <span class="badge bg-primary">Reserviert</span>
                                        @elseif($obj->status == 4)
                                            <span class="badge bg-secondary">Abgesagt</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end">
                                            <div>
                                                <a href="{{ route('admin.reservation.edit', ['id' => $obj->id]) }}"
                                                    title="Reservierung bearbeiten">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </div>
                                            <div class="mx-2">
                                                @if ($obj->status != 4)
                                                    <button class="no-style actionBtn"
                                                        onclick="cancelReservation('{{ $obj->id }}')"
                                                        title="Reservierung stornieren">
                                                        <i class="fa fa-cancel"></i>
                                                    </button>
                                                @endif
                                            </div>
                                            <div>
                                                <button class="no-style actionBtn"
                                                    onclick="deleteRecord('{{ $obj->id }}')"
                                                    title="Reservierung löschen">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="paginate">
                    {{ $reservations->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade" id="deleteRecord" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2 pb-1">Reservierung löschen</h3>
                    </div>
                    <p class="text-center">Sind Sie sicher, dass Sie diese Reservierung löschen möchten?</p>
                    <form class="row g-3" method="POST" action="{{ route('admin.reservation.delete') }}">
                        @csrf
                        <div class="col-12 d-flex justify-content-center">
                            <input type="hidden" name="id" id="id" />
                            <button type="submit" class="btn btn-danger me-sm-3 me-1">Ja, löschen</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                aria-label="Close">Abbrechen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Reservation Modal --}}
    <div class="modal fade" id="cancelReservation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2 pb-1">Reservierung stornieren</h3>
                    </div>
                    <p class="text-center">Sind Sie sicher, dass Sie diese Reservierung stornieren möchten?</p>
                    <form class="row g-3" method="POST" action="{{ route('admin.reservation.cancel') }}">
                        @csrf
                        <div class="col-12 d-flex justify-content-center">
                            <input type="hidden" name="id" id="id" />
                            <button type="submit" class="btn btn-danger me-sm-3 me-1">Ja, stornieren</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                aria-label="Close">Stornieren</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('extra_js')
    <script src="assets/vendor/libs/bootstrap-select/bootstrap-select.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>

    <script>
        var order = 'asc';

        async function ajaxSearch(route, method, token, id, keyword = '', type = 1) {
            var status = 'all';

            if (type !== 1) {
                status = keyword;
                keyword = "";
            }

            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('sl')) {
                status = urlParams.get('sl');
            }

            $.ajax({
                url: route,
                method: method,
                data: {
                    _token: token,
                    keyword: keyword,
                    order: order,
                    status: status
                },
                success: function(res) {
                    let html = '';
                    if (res.length > 0) {
                        res.forEach(obj => {
                            var customer_preview_url =
                                `{{ route('admin.customers.preview', ['id' => '__PARAM__']) }}`;
                            var reservation_edit_url =
                                `{{ route('admin.reservation.edit', ['id' => '__PARAM__']) }}`;

                            customer_preview_url = customer_preview_url.replace('__PARAM__', obj.dog
                                ?.customer?.id)
                            reservation_edit_url = reservation_edit_url.replace('__PARAM__', obj.id)
                            html += '<tr>';
                            html += `<td>${obj.dog?.id}</td>`;
                            html += `<td>${obj.dog?.name}</td>`;
                            html += `<td>`;
                            html += `<a href="/admin/customers/${obj.dog?.customer?.id}/preview">`;
                            html += `${obj.dog?.customer?.name} (${obj.dog?.customer?.id_number})`;
                            html += `</a>`;
                            html += `</td>`;
                            html += `<td>${obj.dog?.customer?.phone}</td>`;
                            if (obj.plan) {
                                html += `<td>${obj.plan.title ? obj.plan.title : ''}</td>`;
                            } else {
                                html += `<td></td>`
                            }
                            var sp1 = obj.checkin_date.split(' ');
                            var date = sp1[0].split('-');
                            var checkin_date = date[2] + '.' + date[1] + '.' + date[0];

                            var sp2 = obj.checkout_date.split(' ');
                            var date2 = sp2[0].split('-');
                            var checkout_date = date2[2] + '.' + date2[1] + '.' + date2[0];
                            html += `<td>${checkin_date}</td>`;
                            html += `<td>${checkout_date}</td>`;
                            html += `<td>`;
                            if (obj.status == 1) // in room
                            {
                                html += `<span class="badge bg-success">Im Zimmer</span>`;
                            } else if (obj.status == 2) // checkout
                            {
                                html += `<span class="badge bg-warning">Kasse</span>`;
                            } else if (obj.status == 3) // reserved
                            {
                                html += `<span class="badge bg-primary">Reserviert</span>`;
                            } else if (obj.status == 4) {
                                html += `<span class="badge bg-secondary">Abgesagt</span>`;
                            }
                            html += `<td><div class="d-flex justify-content-end">`;
                            html += `<div>`;
                            html +=
                                `<a href="/admin/reservation/${obj.id}/edit" title="Reservierung bearbeiten">`;
                            html += `<i class="fa fa-edit"></i>`;
                            html += `</a>`;
                            html += `</div>`;
                            html += `<div class="mx-2">`;
                            if (obj.status != 4) {
                                html +=
                                    `<button class="no-style actionBtn" onclick="cancelReservation('${obj.id}')" title="Reservierung stornieren"><i class="fa fa-cancel"></i></button>`
                            }
                            html += `</div>`;
                            html += `<div class="">`;
                            html +=
                                `<button class="no-style actionBtn" onclick="deleteRecord('${obj.id}')" title="Reservierung löschen"><i class="fa fa-trash"></i></button>`;
                            html += `</div>`;
                            html += `</div>`;
                            html += `</td>`;
                            html += '</tr>';
                        });
                        $("#myTable tbody").html(html);
                    }
                }
            })
        }

        function deleteRecord(id) {
            $("#deleteRecord #id").val(id);
            $("#deleteRecord").modal('show');
        }

        function cancelReservation(id) {
            $("#cancelReservation #id").val(id);
            $("#cancelReservation").modal('show');
        }

        async function sortCheckin(route, method, token, id) {
            await ajaxSearch(route, method, token, id);
            order = (order == 'asc') ? 'desc' : 'asc';
        }

        function filterStatus() {
            var sl = $("#statusInput").val()
            window.location.href = '{{ route('admin.reservation') }}?sl=' + sl;
            // ajaxSearch("{{ route('admin.reservation') }}", 'GET', '{{ csrf_token() }}', 'myTable', sl,2)
        }
    </script>
@endsection
