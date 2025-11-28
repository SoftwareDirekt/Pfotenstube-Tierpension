{{-- resources/views/admin/reservation/index.blade.php --}}
@extends('admin.layouts.app')

@section('title')
    <title>Reservierung</title>
@endsection

@section('extra_css')
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/bootstrap-select/bootstrap-select.css" />
    <link rel="stylesheet" href="assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css" />
    <link rel="stylesheet" href="assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css" />
    <style>
        .btn-th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: #222222;
        }

        #filtersContainer {
            display: none;
        }

        #filtersContainer.show {
            display: block;
        }
    </style>
@endsection

@section('body')
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="card">
            <div class="row align-items-center px-3 py-2">
                <div class="col-md-3">
                    <h5 class="card-header">Reservierung Management</h5>
                </div>
                <div class="col-md-9">
                    <div class="d-flex align-items-center gap-2 justify-content-end">
                        <button type="button" class="btn btn-primary" onclick="toggleFilters()">
                            <i class="fa fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('admin.reservation.add.view') }}" class="btn btn-success">
                            <i class="fa fa-plus"></i> Neuer Reservierung
                        </a>
                        <button type="button" class="btn btn-info" onclick="exportData()">
                            <i class="fa fa-file-excel me-1"></i> Export
                        </button>
                    </div>
                </div>
                <div id="filtersContainer" class="mt-3 {{ request()->has('keyword') || request()->has('status') || request()->has('date_range') || request()->has('per_page') ? 'show' : '' }}">
                    <form method="GET" action="{{ route('admin.reservation') }}" class="row g-3">
                        {{-- STATUS --}}
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status[]" id="statusInput" class="form-control select2" multiple>
                                <option value="all"   {{ in_array('all', (array)$status) ? 'selected' : '' }}>All Records</option>
                                <option value="1"     {{ in_array('1',     (array)$status) ? 'selected' : '' }}>Im Zimmer</option>
                                <option value="2"     {{ in_array('2',     (array)$status) ? 'selected' : '' }}>Kasse</option>
                                <option value="3"     {{ in_array('3',     (array)$status) ? 'selected' : '' }}>Reserviert</option>
                                <option value="4"     {{ in_array('4',     (array)$status) ? 'selected' : '' }}>Abgesagt</option>
                            </select>
                        </div>

                        {{-- KEYWORD --}}
                        <div class="col-md-2">
                            <label class="form-label">Search</label>
                            <input
                                type="text"
                                name="keyword"
                                value="{{ $keyword }}"
                                class="form-control"
                                placeholder="Search"
                            >
                        </div>

                        {{-- DATE RANGE --}}
                        <div class="col-md-2">
                            <label class="form-label">Datum</label>
                            <input
                                type="text"
                                name="date_range"
                                id="dateRangeInput"
                                value="{{ str_replace('+', ' ', request('date_range')) }}"
                                class="form-control"
                                placeholder="Von - Bis"
                            >
                        </div>

                        {{-- PAGINATION SELECT --}}
                        <div class="col-md-2">
                            <label class="form-label">Anzahl</label>
                            <select name="per_page" id="perPageSelect" class="form-control select2">
                                <option value="30" {{ request('per_page', '30') == '30' ? 'selected' : '' }}>30</option>
                                <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                                <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Alle</option>
                            </select>
                        </div>

                        <div class="col-md-3" style="margin-top: 46px;">
                            <button type="submit" class="btn btn-primary me-2">Anwenden</button>
                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">Zurücksetzen</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">

                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Hund ID</th>
                        <th>Hund Name</th>
                        <th>Kunde</th>
                        <th>Telefonnummer</th>
                        <th>Preisplan</th>
                        @php
                            $icon = $order === 'asc'
                              ? 'fa-chevron-up'
                              : 'fa-chevron-down';

                            $newOrder = $order === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <th>
                            <form method="GET" action="{{ route('admin.reservation') }}" class="d-inline">
                                <input type="hidden" name="keyword" value="{{ $keyword }}">
                                @foreach ((array)$status as $s)
                                    <input type="hidden" name="status[]" value="{{ $s }}">
                                @endforeach

                                <input type="hidden" name="order" value="{{ $newOrder }}">

                                <button type="submit" class="btn bg-transparent shadow-none p-0 border-0 btn-th">
                                    <i class="fa {{ $icon }}"></i> Einchecken
                                </button>
                            </form>
                        </th>
                        <th>Auschecken</th>
                        <th>Status</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($reservations as $r)
                        @continue(!$r->dog)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->dog->id }}</td>
                            <td>{{ $r->dog->name }}</td>
                            <td>
                                <a href="{{ route('admin.customers.preview', $r->dog->customer->id) }}">
                                    {{ $r->dog->customer->name }}
                                    ({{ $r->dog->customer->id_number }})
                                </a>
                            </td>
                            <td>{{ $r->dog->customer->phone }}</td>
                            <td>{!! $r->plan->title ?? '' !!}</td>
                            <td>{{ $r->checkin_date->format('d.m.Y') }}</td>
                            <td>{{ optional($r->checkout_date)->format('d.m.Y') }}</td>
                            <td>
                                @if ($r->status == 1)
                                    <span class="badge bg-success">Im Zimmer</span>
                                @elseif ($r->status == 2)
                                    <span class="badge bg-warning">Kasse</span>
                                @elseif ($r->status == 3)
                                    <span class="badge bg-primary">Reserviert</span>
                                @elseif ($r->status == 4)
                                    <span class="badge bg-secondary">Abgesagt</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{-- Hide edit button for checked-out reservations (status 2) - they have payments --}}
                                @if ($r->status != 2)
                                    <a href="{{ route('admin.reservation.edit', $r->id) }}" class="mx-1" title="Bearbeiten">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                @endif
                                {{-- Hide cancel button for checked-out (status 2) and cancelled (status 4) reservations --}}
                                @if ($r->status != 2 && $r->status != 4)
                                    <form
                                        action="{{ route('admin.reservation.cancel') }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Möchten Sie wirklich stornieren?')"
                                    >
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $r->id }}">
                                        <button type="submit" class="btn btn-link p-0 mx-1" title="Stornieren">
                                            <i class="fa fa-ban text-warning"></i>
                                        </button>
                                    </form>
                                @endif
                                {{-- Hide delete button for checked-out reservations (status 2) - they have payments --}}
                                @if ($r->status != 2)
                                    <form
                                        action="{{ route('admin.reservation.delete') }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Möchten Sie wirklich löschen?')"
                                    >
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $r->id }}">
                                        <button type="submit" class="btn btn-link p-0 mx-1" title="Löschen">
                                            <i class="fa fa-trash text-danger"></i>
                                        </button>
                                    </form>
                                @endif
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

@endsection

@section('extra_js')
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script>
        // Initialize date range picker
        function initDateRangePicker() {
            // Check if jQuery and daterangepicker are available
            if (typeof $ === 'undefined' || typeof $.fn.daterangepicker === 'undefined') {
                console.error('jQuery or daterangepicker not loaded');
                return;
            }

            const $input = $('#dateRangeInput');
            
            // Destroy existing picker if it exists
            if ($input.data('daterangepicker')) {
                $input.data('daterangepicker').remove();
            }

            // Initialize date range picker
            $input.daterangepicker({
                locale: {
                    format: 'DD.MM.YYYY',
                    separator: ' - ',
                    applyLabel: 'Anwenden',
                    cancelLabel: 'Abbrechen',
                    fromLabel: 'Von',
                    toLabel: 'Bis',
                    customRangeLabel: 'Benutzerdefiniert',
                    weekLabel: 'W',
                    daysOfWeek: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
                    monthNames: [
                        'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
                    ],
                    firstDay: 1
                },
                opens: 'left',
                autoUpdateInput: false
            });

            // Update form input when date range is selected
            $input.on('apply.daterangepicker', function (ev, picker) {
                $(this).val(
                    picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format('DD.MM.YYYY')
                );
            });

            // Clear input when cancelled
            $input.on('cancel.daterangepicker', function () {
                $(this).val('');
            });
        }

        // Toggle filters container
        function toggleFilters() {
            const container = document.getElementById('filtersContainer');
            container.classList.toggle('show');

            if (container.classList.contains('show')) {
                setTimeout(() => initDateRangePicker(), 100);
            }
        }

        // Reset filters
        function resetFilters() {
            window.location.href = '{{ route("admin.reservation") }}';
        }

        // Initialize Select2 and date picker
        $(document).ready(function () {
            $('.select2').select2({ width: '100%' });

            if ($('#filtersContainer').hasClass('show')) {
                initDateRangePicker();
            }
        });

        // Export data - uses current filter parameters
        function exportData() {
            const currentParams = new URLSearchParams(window.location.search);

            // Remove page and per_page for full export
            currentParams.delete('page');
            currentParams.delete('per_page');

            // Parse date_range safely (handle + and extra spaces)
            let dateRange = $('#dateRangeInput').val() || currentParams.get('date_range');
            if (dateRange) {
                dateRange = decodeURIComponent(dateRange).replace(/\+/g, ' ').trim();
                const dates = dateRange.split(/\s*-\s*/);

                if (dates.length === 2) {
                    const convertDate = (dateStr) => {
                        const parts = dateStr.trim().split('.');
                        if (parts.length !== 3) return null;
                        return `${parts[2]}-${parts[1]}-${parts[0]}`;
                    };

                    const dateFrom = convertDate(dates[0]);
                    const dateTo = convertDate(dates[1]);

                    if (dateFrom && dateTo) {
                        currentParams.set('date_from', dateFrom);
                        currentParams.set('date_to', dateTo);
                        currentParams.delete('date_range');
                    }
                }
            }

            window.location.href =
                '{{ route("admin.reservation.export") }}?' + currentParams.toString();
        }
    </script>
@endsection
