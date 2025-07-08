@extends('admin.layouts.app')

@section('title')
    <title>Hundekalender</title>
@endsection

@section('extra_css')
    <style>
        .badge2 {
            color: #636578;
            background-color: #fff;
            font-size: 22px;
            border-radius: 5px;
            text-align: left;
            padding: 0;
        }

        .calendar-table th,
        .calendar-table td {
            width: 25px;
            font-size: 14px;
            text-align: center;
            padding: 4px;
        }

        .free {
            color: #666 !important;
            font-weight: 600;
        }
    </style>
@endsection

@section('body')
    @php
        use Carbon\Carbon;
        $current = Carbon::now()->startOfMonth()->addMonths($incrementMonth);
    @endphp

    <div class="px-4 flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Reservierung: {{ $total_reservations }}</h4>
                    <div class="dropdown">
                        <a class="badge badge2 badge-secondary dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            {{ $monthAndYear }}
                        </a>
                        <div class="dropdown-menu" style="max-height:300px; overflow:auto;">
                            @foreach($months as $i => $label)
                                <a class="dropdown-item" href="{{ route('admin.dog.calendar', ['month' => $i]) }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered calendar-table">
                        <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            @for($day = 1; $day <= $daysInMonth; $day++)
                                <th data-bs-toggle="tooltip"
                                    title="UV: {{ $compatibles[$day]['UV'] }}  V: {{ $compatibles[$day]['V'] }}  VJ: {{ $compatibles[$day]['VJ'] }}  VM: {{ $compatibles[$day]['VM'] }}  S: {{ $compatibles[$day]['S'] }}">
                                    {{ str_pad($day, 2, '0', STR_PAD_LEFT) }}
                                </th>
                            @endfor
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($matrix as $idx => $row)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                @php $d = 1; @endphp
                                @while($d <= $daysInMonth)
                                    @if(is_null($row[$d]))
                                        <td class="free">{{ $d }}</td>
                                        @php $d++; @endphp
                                    @else
                                        @php
                                            $res = $row[$d];
                                            // Determine span
                                            $startDay = $d;
                                            $endDay   = $d;
                                            while($endDay + 1 <= $daysInMonth && isset($row[$endDay + 1]) && $row[$endDay + 1]?->id === $res->id) {
                                                $endDay++;
                                            }
                                            $length = $endDay - $startDay + 1;
                                            // Color by compatibility
                                            switch($res->dog->compatibility) {
                                                case 'UV': $bg = 'red'; break;
                                                case 'V':  $bg = 'green'; break;
                                                case 'VJ': $bg = 'blue'; break;
                                                case 'VM': $bg = 'deeppink'; break;
                                                case 'S':  $bg = 'orange'; break;
                                                default:   $bg = 'gray';
                                            }
                                            // Continuation symbol
                                            $isCont = Carbon::parse($res->checkin_date)->lt($current->copy()->startOfMonth());
                                            $symbol = $isCont ? '--->' : '';
                                            if ($length === 1) {
                                                $symbol = '+';
                                            }
                                        @endphp

                                        <td colspan="{{ $length }}"
                                            style="background-color: {{ $bg }}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                            @if($isCont) class="text-start" @endif
                                            data-bs-toggle="tooltip"
                                            title="{{ $res->dog->name }} ({{ $res->dog->id }}) - {{ $res->dog->compatibility }}">
                                            {{ $symbol }}
                                        </td>
                                        @php $d = $endDay + 1; @endphp
                                    @endif
                                @endwhile
                            </tr>
                        @endforeach
                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
                .forEach(el => new bootstrap.Tooltip(el));
        });
    </script>
@endsection
