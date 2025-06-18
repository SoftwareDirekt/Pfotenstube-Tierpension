@extends('admin.layouts.app')
@section('title')
    <title>Reservierung hinzufügen</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
<link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
<link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="assets/vendor/libs/flatpickr/flatpickr.css" />
<link rel="stylesheet" href="assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css" />
<link rel="stylesheet" href="assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css" />
<link rel="stylesheet" href="assets/vendor/libs/jquery-timepicker/jquery-timepicker.css" />
<link rel="stylesheet" href="assets/vendor/libs/pickr/pickr-themes.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reservierung bearbeiten - {{$reservation->dog->name}} ({{$reservation->dog->id}})</h5>
                {{-- <div class="d-flex justify-content-end">
                    <button type="button" class="no-style" onclick="addData()" style="color: #5a5fe0">
                        <i class="fa fa-plus"></i> Daten hinzufügen
                    </button>
                </div> --}}
            </div>
            <div class="card-body">
              <form action="{{route('admin.reservation.update')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="flatpickr-range" class="hidden" />
                <hr>
                <div id="additionals" class="row">
                    {{-- @foreach($reservations as $obj) --}}
                    <div class="col-md-6 mb-4" id="ranger0">
                        {{-- @if($loop->index > 0)
                        <div class="d-flex justify-content-end">
                            <button class="no-style text-danger" onclick="removeRange({{$loop->index}})">
                                <i class="fa fa-close"></i> Daten entfernen
                            </button>
                        </div>
                        @endif --}}
                        <div class="form-floating form-floating-outline">
                            <input type="text" id="range0" name="dates" class="form-control bs-rangepicker-basic" value="{{$reservation->res_date}}" required/>
                            <label for="range0">Einchecken - Auschecken</label>
                        </div>
                    </div>
                    {{-- @endforeach --}}
                </div>
                <input type="hidden" name="dog_id" value="{{$reservation->dog->id}}">
                <input type="hidden" name="res_id" value="{{$reservation->id}}" required>
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{route('admin.reservation')}}">'
                    <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                </a>
              </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('extra_js')
<script src="assets/vendor/libs/select2/select2.js"></script>
<script src="assets/vendor/libs/moment/moment.js"></script>
<script src="assets/vendor/libs/flatpickr/flatpickr.js"></script>
<script src="assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js"></script>
<script src="assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
<script src="assets/vendor/libs/jquery-timepicker/jquery-timepicker.js"></script>
<script src="assets/vendor/libs/pickr/pickr.js"></script>
<script src="assets/js/forms-pickers.js"></script>
<script>
    function addData()
    {
        let count = $("#additionals").children('div').length;
        
        let html = '<div class="col-md-6 mb-4" id="ranger'+count+'">';
                html += '<div class="d-flex justify-content-end">';
                    html += '<button class="no-style text-danger" onclick="removeRange('+count+')"><i class="fa fa-close"></i> Daten entfernen</button>';
                html += '</div>';
                html += '<div class="form-floating form-floating-outline">';
                    html += '<input type="text" id="range'+count+'" name="dates[]" class="bs-rangepicker-basic form-control" />';
                    html += '<label for="range'+count+'">Einchecken - Auschecken</label>';
                html += '</div>';
            html += '</div>';
        $("#additionals").append(html)
        $("#range" + count).daterangepicker({
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Speichern',
                cancelLabel: 'Zurück',
                fromLabel: 'Von',
                toLabel: 'Bis',
                customRangeLabel: 'Benutzerdefiniert',
                weekLabel: 'W',
                daysOfWeek: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
                monthNames: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
                firstDay: 1
            },
            opens: 'left',
            minDate: moment().startOf('day')
        });
    }

    function removeRange(id)
    {
        $("#ranger"+id).remove();
    }
</script>
@endsection