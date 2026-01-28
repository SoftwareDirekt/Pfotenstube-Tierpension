@extends('admin.layouts.pdfLayout')
@section('title')
    <title>Arbeitszeitaufzeichnungen</title>
@endsection

@section('extra_css')
    <style>
        .table th,
        body {
            font-size: 11px !important;
        }
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        th {
            text-align: center;
            vertical-align: middle;
        }
        td {
            text-align: left;
        }
        th, td {
            border: 1px solid #333;
            padding: 4px;
            font-size: 11px;
        }
        thead {
            display: table-header-group; /* header on each page */
        }
        tfoot {
            display: table-footer-group; /* footer on each page */
        }
        .text-center {
            text-align: center;
        }
    </style>
@endsection
@section('body')
    <div class="row mt-5">
        <div class="col-md-12 text-center my-2">
            <h2 class="card-header">Arbeitszeiterfassung</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 my-2">
            <p class="card-header float-start"><strong>Name:</strong> {{$employee->name ?? '-'}}</p>
            <p class="card-header float-end"><strong>Monat und Jahr:</strong> {{$selected_month}}/{{$selected_year}}</p>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <table class="table table-bordered">
                <thead class="table-light">
                <tr class="align-middle text-center">
                    <th style="width:20px;">Tag</th>
                    <th style="width: 15%">Arbeitsbeginn</th>
                    <th style="width: 15%">Arbeitsende</th>
                    <th style="width: 15%">Tagesarbeitszeit <br/>(ohne Pausen)</th>
                    <th style="width: 15%">Pausen <br/>(von - bis)</th>
                    <th>Notizen</th>
                </tr>
                </thead>
                <tbody>
                @foreach($days as $dayData)
                    <tr>
                        <td class="px-2 py-1 text-center">{{ $dayData['date']->format('d') }}</td>
                        <td class="px-2 py-1 text-center">
                            {{ $dayData['start_time'] ?? '' }}
                        </td>
                        <td class="px-2 py-1 text-center">
                            {{ $dayData['end_time'] ?? '' }}
                        </td>
                        <td class="px-2 py-1 text-center">
                            {{ $dayData['hours'] ?? '' }}
                        </td>
                        <td class="px-2 py-1 text-center"></td>
                        <td class="px-2 py-1" style="font-size: 10px;">
                            {{ $dayData['notes'] ?? '' }}
                        </td>
                    </tr>
                @endforeach
                {{-- Totals Row --}}
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td class="px-2 py-1 text-center" colspan="3">
                        <strong>Gesamt</strong>
                    </td>
                    <td class="px-2 py-1 text-center" style="color: #0d6efd; font-size: 13px;">
                        <strong>{{ $total_hours ?? '00:00' }}</strong>
                    </td>
                    <td class="px-2 py-1 text-center" colspan="2">
                        <strong>{{ $total_days_worked ?? 0 }} Tage</strong>
                    </td>
                </tr>
            </table>

        </div>
    </div>

@endsection
