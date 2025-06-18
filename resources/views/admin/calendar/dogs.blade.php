@extends('admin.layouts.app')
@section('title')
    <title>Hundekalender</title>
@endsection
@section('extra_css')
    <style>
        .badge2 {
            color: #636578 !important;
            background-color: white !important;
            font-size: 22px !important;
            border-radius: 5px !important;
            text-align: left!important;
            padding: 0!important;
        }
    </style>
@endsection
@section('body')
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="row gy-4">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Reservierung: {{$total_reservations ?? 0}}</h4>
                                </div>
                                <div class="col-md-6 d-flex justify-content-end">
                                    <div class="currentDate text-start dropdown">
                                        <a class="dropdown-toggle badge badge2 badge-secondary" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ $monthAndYear }}</a>
                                        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink" style="height: 300px;overflow:scroll">
                                            @php \Carbon\Carbon::setLocale('de'); @endphp
                                            @for ($i = 0; $i <= 130; $i++)
                                                <a class="dropdown-item" href="{{route('admin.dog.calendar',['month' => $i])}}">
                                                    {{ \Carbon\Carbon::now()->addMonths($i)->isoFormat('MMMM Y') }}
                                                </a>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            {{-- <h2>
                                <div class="d-flex justify-content-between pt-2">
                                    <a href="{{route('admin.dog.calendar',['month' => -1+$incrementMonth])}}" class="prevDay text-dark"><i class="fa fa-arrow-left" aria-hidden="true"></i></a>
                                    <a href="{{route('admin.dog.calendar',['month' => 1+$incrementMonth])}}" class="nextDay text-dark"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </h2> --}}
                            <div class="position-relativ">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="myTable">
                                        <thead class="bg-light">
                                            {!! $tableHead !!}
                                        </thead>
                                        <tbody>
                                            {!! $tableBody !!}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
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
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        });
    </script>
@endsection
