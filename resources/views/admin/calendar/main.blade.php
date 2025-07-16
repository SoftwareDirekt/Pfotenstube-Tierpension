@extends('admin.layouts.app')
@section('title')
    <title>Kalendar</title>
@endsection
@section('extra_css')
    {{-- <link href="{{ asset('assets/css/fullcalendar.css') }}" rel="stylesheet" /> --}}
    {{-- <link href="{{ asset('assets/css/fullcalendar.print.css') }}" rel="stylesheet" media="print" />--}}
    <style>

    </style>
@endsection
@section('body')
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div id="calendar2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    @include('modals.createEventModal')
    @include('modals.calendarModal')
@endsection
@section('extra_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment-with-locales.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    {{-- <script src="{{ asset('assets/js/fullcalendar.js') }}"></script> --}}
    <script>

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })

            $("#statusd").on('change', function(){
                $("#title").prop('readonly', true);
                if($(this).val() == '')
                    $("#title").prop('readonly', false);
            })
            $("#statusEdit").on('change', function(){
                $("#titleEdit").prop('readonly', true);
                if($(this).val() == '')
                    $("#titleEdit").prop('readonly', false);
            })
            moment.locale('de_AT');



            // document.addEventListener('DOMContentLoaded', function() {
                // var events = getEvents();
                // console.log('envet', events);
                const calendarEl = document.getElementById('calendar2')
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    // initialView: 'timeGridDay',
                    initialView: 'dayGridMonth',
                    themeSystem: 'bootstrap5',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        // right: 'month,agendaWeek,agendaDay'
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    dayHeaderFormat: {
                        weekday: 'long'
                    },
                    columnHeaderFormat: {
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit'
                    },
                    buttonText: {
                        today: 'Heute',
                        day: 'Tag',
                        week:'Woche',
                        month:'Monat'
                    },
                    views: {
                        dayGridMonth: {
                            // titleFormat: { year: 'numeric', month: '2-digit', day: '2-digit' },
                        },
                        timeGridWeek: {
                            // titleFormat: { year: 'numeric', month: '2-digit', day: '2-digit' },
                            // eventTimeFormat: { hour12: false, hour: '2-digit', minute: '2-digit' },

                        },
                        timeGridDay: {

                            // eventTimeFormat: { hour12: false, hour: '2-digit', minute: '2-digit' },
                        }
                    },
                    displayEventEnd:true,
                    timeFormat: 'HH:mm',
                    // slotLabelFormat:"HH:mm",
                    defaultView: 'agendaWeek',
                    locale: 'de-AT',
                    displayEventTime: true,
                    editable: true,
                    // eventResizableFromStart: true,
                    selectable: true,
                    allDaySlot: false,
                    // durationEditable: true,
                    events: "/admin/events",
                    eventRender: function (event, element, view) {
                        if (event.allDay === 'true') {
                            event.allDay = true;
                        } else {
                            event.allDay = false;
                        }
                    },
                    eventClick: function(event, jsEvent, view) {
                        event = event.event;
                        // endtime = $.fullCalendar.moment(event.end).format('h:mm');
                        endtime = moment(event.end).format('h:mm');
                        starttime = moment(event.start).format('dddd, MMMM Do YYYY HH:mm:ss') + " - " + moment(event.end).format('dddd, MMMM Do YYYY HH:mm:ss');
                        var mywhen = starttime;
                        $(".eventShowing").show()
                        $(".eventEditing").hide()
                        $('#modalTitle').html(event.title+' <a href="javascript:void(0);" id="editEventClicked"><i class="fa fa-pencil" aria-hidden="true"></i></a>');
                        $('#modalWhen').text(mywhen);
                        $('#eventID').val(event.id);
                        $('#calendarModal').modal('show');
                    },

                    select: function(dates) {
                        start = dates.start;
                        end = dates.end;
                        // endtime = $.fullCalendar.moment(end).format('h:mm');
                        endtime = moment(end).format('h:mm');
                        starttime = moment(start).format('dddd, MMMM Do YYYY HH:mm:ss') + " - " + moment(end).format('dddd, MMMM Do YYYY HH:mm:ss');
                        var mywhen = starttime;
                        start = moment(start).format("YYYY-MM-DD HH:mm:ss");
                        end = moment(end).format("YYYY-MM-DD HH:mm:ss");

                        $('#createEventModal #startTime').val(start);
                        $('#createEventModal #endTime').val(end);
                        $('#createEventModal #modalWhenAdd').text(mywhen);
                        $('#createEventModal').modal('toggle');
                    },
                    eventDrop: function(e, delta) {
                        var event = e.event;
                        $.ajax({
                            url: '/admin/events/update',
                            data: {
                                action: 'update',
                                title: event.title,
                                start: moment(event.start).format(),
                                end: moment(event.end).format(),
                                id: event.id
                            },
                            type: "POST",
                            success: function(json) {
                            }
                        });
                    },
                    eventResize: function(e) {
                        var event = e.event;
                        $.ajax({
                            url: '/admin/events/update',
                            data: {
                                action: 'update',
                                title: event.title,
                                start: moment(event.start).format(),
                                end: moment(event.end).format(),
                                id: event.id
                            },
                            type: "POST",
                            success: function(json) {
                            }
                        });
                    }
                })
                calendar.render();
            // })

            // var calendar = $('#calendar2').fullCalendar({
            //     header: {
            //         left: 'prev,next today',
            //         center: 'title',
            //         right: 'month,agendaWeek,agendaDay'
            //     },
            //     buttonText: {
            //         today: 'Heute',
            //         day: 'Tag',
            //         week:'Woche',
            //         month:'Monat'
            //     },
            //     views: {
            //         month: {
            //             columnFormat: 'dddd',
            //         },
            //         week: {
            //             columnFormat: 'ddd D.M',
            //         },
            //         day: {
            //             columnFormat: 'dddd',
            //         }
            //     },
            //     displayEventEnd:true,
            //     timeFormat: 'HH:mm',
            //     slotLabelFormat:"HH:mm",
            //     defaultView: 'agendaWeek',
            //     locale: 'de_AT',
            //     displayEventTime: true,
            //     editable: true,
            //     // eventResizableFromStart: true,
            //     selectable: true,
            //     allDaySlot: false,
            //     // durationEditable: true,
            //     events: "/admin/events",
            //     eventRender: function (event, element, view) {
            //         if (event.allDay === 'true') {
            //             event.allDay = true;
            //         } else {
            //             event.allDay = false;
            //         }
            //     },
            //     eventClick: function(event, jsEvent, view) {
            //         endtime = $.fullCalendar.moment(event.end).format('h:mm');
            //         starttime = $.fullCalendar.moment(event.start).format('dddd, MMMM Do YYYY HH:mm:ss') + " - " + $.fullCalendar.moment(event.end).format('dddd, MMMM Do YYYY HH:mm:ss');
            //         var mywhen = starttime;
            //         $(".eventShowing").show()
            //         $(".eventEditing").hide()
            //         $('#modalTitle').html(event.title+' <a href="javascript:void(0);" id="editEventClicked"><i class="fa fa-pencil" aria-hidden="true"></i></a>');
            //         $('#modalWhen').text(mywhen);
            //         $('#eventID').val(event.id);
            //         $('#calendarModal').modal('show');
            //         console.log('sssssssssss')
            //     },

            //     select: function(start, end, jsEvent) {
            //         endtime = $.fullCalendar.moment(end).format('h:mm');
            //         starttime = $.fullCalendar.moment(start).format('dddd, MMMM Do YYYY HH:mm:ss') + " - " + $.fullCalendar.moment(end).format('dddd, MMMM Do YYYY HH:mm:ss');
            //         var mywhen = starttime;
            //         start = moment(start).format("YYYY-MM-DD HH:mm:ss");
            //         end = moment(end).format("YYYY-MM-DD HH:mm:ss");

            //         $('#createEventModal #startTime').val(start);
            //         $('#createEventModal #endTime').val(end);
            //         $('#createEventModal #modalWhenAdd').text(mywhen);
            //         $('#createEventModal').modal('toggle');
            //     },
            //     eventDrop: function(event, delta) {
            //         $.ajax({
            //             url: '/admin/events/update',
            //             data: {
            //                 action: 'update',
            //                 title: event.title,
            //                 start: moment(event.start).format(),
            //                 end: moment(event.end).format(),
            //                 id: event.id
            //             },
            //             type: "POST",
            //             success: function(json) {
            //             }
            //         });
            //     },
            //     eventResize: function(event) {
            //         console.log('resize')
            //         $.ajax({
            //             url: '/admin/events/update',
            //             data: {
            //                 action: 'update',
            //                 title: event.title,
            //                 start: moment(event.start).format(),
            //                 end: moment(event.end).format(),
            //                 id: event.id
            //             },
            //             type: "POST",
            //             success: function(json) {
            //             }
            //         });
            //     }
            // });

            $('#submitButton').on('click', function(e) {
                e.preventDefault();
                doSubmit();
            });
            $(document).on('click','#editEventClicked', function(e) {
                $(".eventShowing").hide()
                $(".eventEditing").show()
                e.preventDefault();
                doGetEvent();
                $("#submitButtonUpdate").removeClass('d-none')
                $("#deleteButton").addClass('d-none')
            })
            $('#calendarModal').on('hidden.bs.modal', function (e) {
                $("#submitButtonUpdate").addClass('d-none')
                $("#deleteButton").removeClass('d-none')
            });
            $('#submitButtonUpdate').on('click', function(e) {
                e.preventDefault();
                doUpdateSubmit();
            });

            $('#deleteButton').on('click', function(e) {
                e.preventDefault();
                doDelete();
            });

            function doDelete() {
                $("#calendarModal").modal('hide');
                var eventID = $('#eventID').val();

                $.ajax({
                    url: '/admin/events/delete',
                    data: { id: eventID },
                    type: "DELETE",
                    success: function(json) {
                        eventz = calendar.getEventById(eventID);
                        eventz.remove();
                    }
                });
            }
            function doGetEvent() {
                var eventID = $('#eventID').val();
                $.ajax({
                    url: '/admin/events/'+eventID,
                    data: { action: 'get', id: eventID },
                    type: "GET",
                    success: function(json) {
                        $("#titleEdit").val(json.title);
                        $("#uidEdit").val(json.uid);
                        $("#statusEdit").val(json.status);
                    }
                });
            }

            function doSubmit() {
                var title = $('#title').val();
                var startTime = $('#startTime').val();
                var endTime = $('#endTime').val();
                var uid = $('#uid').val();
                var status = $('#statusd').val();
                $('#title').val("");

                $("#createEventModal").modal('hide');
                $.ajax({
                    url: '/admin/events',
                    data: {
                        action: 'add',
                        title: title,
                        start: startTime,
                        end: endTime,
                        uid: uid,
                        status: status
                    },
                    type: "POST",
                    success: function(json) {
                        calendar.refetchEvents();
                        // $('#calendar2').fullCalendar('refetchEvents');
                    }
                });
            }

            function doUpdateSubmit() {
                var title = $('#titleEdit').val();
                var uid = $('#uidEdit').val();
                var status = $('#statusEdit').val();
                var eventID = $('#eventID').val();
                $('#title').val("");

                $("#calendarModal").modal('hide');
                $.ajax({
                    url: '/admin/events/update',
                    data: {
                        action: 'edit',
                        title: title,
                        uid: uid,
                        eventID: eventID,
                        status: status
                    },
                    type: "POST",
                    success: function(json) {
                        $('#calendar2').fullCalendar('refetchEvents');
                    },
                });
            }
        });
    </script>
@endsection
