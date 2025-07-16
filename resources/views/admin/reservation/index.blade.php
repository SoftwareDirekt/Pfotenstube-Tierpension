{{-- resources/views/admin/reservation/index.blade.php --}}
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

        .btn-th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: #222222;
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
                    <form method="GET" action="{{ route('admin.reservation') }}" class="row gx-2 gy-2 justify-content-end">
                        {{-- STATUS --}}
                        <div class="col-auto">
                            <select name="status[]" id="statusInput" class="form-control select2" multiple>
                                <option value="all"   {{ in_array('all', (array)$status) ? 'selected' : '' }}>All Records</option>
                                <option value="1"     {{ in_array('1',     (array)$status) ? 'selected' : '' }}>Im Zimmer</option>
                                <option value="2"     {{ in_array('2',     (array)$status) ? 'selected' : '' }}>Kasse</option>
                                <option value="3"     {{ in_array('3',     (array)$status) ? 'selected' : '' }}>Reserviert</option>
                                <option value="4"     {{ in_array('4',     (array)$status) ? 'selected' : '' }}>Abgesagt</option>
                            </select>
                        </div>

                        {{-- KEYWORD --}}
                        <div class="col-auto">
                            <input
                                type="text"
                                name="keyword"
                                value="{{ $keyword }}"
                                class="form-control"
                                placeholder="Search"
                            >
                        </div>



                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>

                        <div class="col-auto">
                            <a href="{{ route('admin.reservation.add.view') }}" class="btn btn-success">
                                <i class="fa fa-plus"></i> Neuer Reservierung
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">

                    <thead class="table-light">
                    <tr>
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
                                <a href="{{ route('admin.reservation.edit', $r->id) }}" class="mx-1" title="Bearbeiten">
                                    <i class="fa fa-edit"></i>
                                </a>
                                @if ($r->status != 4)
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
    <script>
        // initialize Select2
        document.addEventListener('DOMContentLoaded', () => {
            $('.select2').select2({ width: '100%' });
        });
    </script>
@endsection
