@extends('admin.layouts.app')
@section('title')
    <title>Dashboard</title>
@endsection
@section('extra_css')
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css"/>
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css"/>
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    {{-- <link rel="stylesheet" href="assets/vendor/libs/flatpickr/flatpickr.css" /> --}}
    <link rel="stylesheet" href="assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css"/>
    <link rel="stylesheet" href="assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css"/>
    <link rel="stylesheet" href="assets/vendor/libs/jquery-timepicker/jquery-timepicker.css"/>
    <link rel="stylesheet" href="assets/vendor/libs/pickr/pickr-themes.css"/>
    <style>
        .baris .select2-container--default .select2-results > .select2-results__options {
            max-height: 450px !important;
            overflow-y: auto;
        }

        .highlight {
            background-color: #f0f8ff;
            border: 2px solid #007bff;
        }

        .ui-sortable-helper {
            z-index: 1000;
        }

        .parent-container {
            overflow: visible;
        }

        /* Hide scrollbars for dog info modal */
        #dogInfo .modal-body::-webkit-scrollbar {
            display: none;
        }
        
        #dogInfo .modal-body {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        /* Ensure no horizontal overflow in modal content */
        #dogInfo .modal-content {
            overflow-x: hidden;
        }

        /* Fix table responsiveness to prevent horizontal scroll */
        #dogInfo .table-responsive {
            overflow-x: hidden;
        }

        #dogInfo .dogInfoTable {
            width: 100%;
            table-layout: fixed;
        }

        #dogInfo .dogInfoTable th,
        #dogInfo .dogInfoTable td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Fix vaccination form to prevent horizontal overflow */
        #dogInfo .vaccination-form .row {
            margin: 0;
        }

        #dogInfo .vaccination-form .col-md-4,
        #dogInfo .vaccination-form .col-md-3,
        #dogInfo .vaccination-form .col-md-2 {
            padding-left: 5px;
            padding-right: 5px;
        }

        /* Ensure form controls don't overflow */
        #dogInfo .form-control {
            max-width: 100%;
        }

        #visitHistory {
            font-size: 0.9rem;
        }

        #visitHistory th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        #visitHistory tfoot tr {
            background-color: #e9ecef;
            border-top: 2px solid #dee2e6;
        }

        #hundeFriends img {
            width: 40px !important;
            height: 40px;
            object-fit: cover;
        }
    </style>
@endsection
@section('body')
    @php $__dogjs = []; @endphp
    <div class="px-4 flex-grow-1 container-p-y kennel">
        <div class="row gx-1">
            <div class="col-md-2 col-lg-2 col-xl-2 col-xxl-2">
                <div class="left" style="background: rgb(255, 182, 193, 0.7)">
                    <div class="reservation_date">
                        <div class="wage">
                            <div>
                                <button class="no-style" onclick="reverseDate()">
                                    <i class="fa fa-chevron-left"></i>
                                </button>
                            </div>
                            <div>
                                <h6 class="date_heading" id="date_heading"
                                    onclick="toggleDateInput()">{{\Carbon\Carbon::now()->format('d.m.Y')}}</h6>
                                <input type="date" class="form-control hidden" id="date_input"
                                       onchange="dateInputChange()"/>
                            </div>
                            <div>
                                <button class="no-style" onclick="forwardDate()">
                                    <i class="fa fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="reservation_counters">
                        <div class="badger">
                            <div>
                                <h5 class="text-primary">Reservierung</h5>
                            </div>
                            <div>
                                <p class="text-danger">{{$total_reservations}}</p>
                            </div>
                        </div>
                        <div class="badger">
                            <div>
                                <h5 class="text-primary">Zimmerbelegung</h5>
                            </div>
                            <div>
                                <p class="text-danger">{{$total_room_occupacy}}</p>
                            </div>
                        </div>
                        <div class="badger">
                            <div>
                                <h5 class="text-primary">Out</h5>
                            </div>
                            <div>
                                <p class="text-danger">{{$total_out}}</p>
                            </div>
                        </div>
                        <div class="badger">
                            <div>
                                <h5 class="text-primary">TSV Hunde</h5>
                            </div>
                            <div>
                                <p class="text-danger">{{$total_orgs}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="reservation_tasks">
                        <h2 class="title">AUFGABENLISTE</h2>
                        <button class="add_style_btn no-styles" onclick="addTodo()">
                            <i class="fa fa-plus"></i>
                            Artikel hinzufügen
                        </button>
                        <hr>
                        <div class="tasks_list">
                            <h2 class="title">Machen</h2>
                            <div id="incompleteTasks">
                                {{-- Ajax Response --}}
                            </div>
                        </div>
                        <hr>
                        <div class="tasks_list completed">
                            <h2 class="title">Vollendet</h2>
                            <div id="completedTasks">
                                {{-- Ajax Response --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-lg-2 col-xl-2 col-xxl-2">
                <div class="center" id="parent_res" style="background: rgb(144, 238, 144,0.8)">

                    <div class="btn-group d-flex justify-content-between">
                        <button type="button" class="btn btn-danger add_style_btn active"
                                style="text-transform: capitalize!important" data-bs-toggle="modal"
                                data-bs-target="#EmployeeAttendenceModal">
                            Mitarbeiter
                        </button>

                        @if(Session::has('lock'))
                            <button id="logoutsessionbutton"
                                    style="border-radius:0;border-top-right-radius:0px!important;background-color: black"
                                    class="btn btn-dark py-2 px-3 fs-5">
                                <i class="fas fa-sign-out"></i>
                            </button>
                        @else
                            <button id="restrictedAccessBtn"
                                    style="border-radius:0;border-top-right-radius:0px!important;background-color: black"
                                    class="btn btn-dark py-2 px-3 fs-5" data-bs-toggle="modal"
                                    data-bs-target="#pinModal">
                                <i class="fas fa-euro-sign"></i>
                            </button>
                        @endif
                    </div>
                    {{--plan----}}


                    <a href="{{route('admin.dogs.in.rooms')}}" class="add_style_btn active"
                       style="border-radius: 0!important">
                        Mehrfachkasse &nbsp;
                        <i class="fa fa-external-link"></i>
                    </a>

                    <div class="btn-group d-flex justify-content-between">
                        <button type="button" class="btn btn-danger add_style_btn active text-capitalize"
                                style="border-radius: 0!important" onclick="openXimmerModal()">
                            Zimmer
                        </button>
                    </div>
                    {{--Timer Button--}}
                    <div id="timerButtonContainer">
                        <a href="#" id="timerButton" class="add_style_btn active"
                           style="border-radius: 0!important" onclick="toggleTimerDropdown(event)">
                            Timer &nbsp;
                            <i class="fa fa-clock"></i>
                        </a>
                        
                        <!-- Dropdown for selecting minutes -->
                        <div id="timerDropdown" style="display: none;">
                            <div style="max-height: 300px; overflow-y: auto;">
                                <div class="dropdown-item" onclick="selectTimerDuration(1)">1 Minute</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(5)">5 Minuten</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(10)">10 Minuten</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(15)">15 Minuten</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(20)">20 Minuten</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(25)">25 Minuten</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(30)">30 Minuten</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(45)">45 Minuten</div>
                                <div class="dropdown-item" onclick="selectTimerDuration(60)">60 Minuten</div>
                            </div>
                        </div>
                        
                        <!-- Running Timer Display -->
                        <div id="timerRunning" style="display: none;">
                            <div class="add_style_btn active" style="border-radius: 0!important; cursor: default;">
                                <span id="timerDisplay">00:00</span>
                            </div>
                            <button onclick="stopTimer()" class="btn btn-danger mt-1" style="width: 100%;">
                                <i class="fa fa-stop me-2"></i> Stop Timer
                            </button>
                        </div>
                    </div>
                    {{--End Timer Button--}}
                    
                    <!-- Audio element for bell sound -->
                    <audio id="timerBellSound" preload="auto">
                        <source src="{{ asset('assets/audio/bell.mp3') }}" type="audio/mpeg">
                        <source src="{{ asset('assets/audio/bell.mp3') }}" type="audio/mpeg">
                    </audio>
                    
                    <div id="profilePicturesContainer" class="d-flex justify-content-center flex-wrap mt-1">
                    </div>

                    <div class="reservationBox">
                        <div class="flex justify-between">
                            <div>
                                <h2 class="dashbox_title">Reservation</h2>
                            </div>
                            <div>
                                <button class="no-style" style="color: #5a5fe0" onclick="addReservation()">
                                    <i class="mdi mdi-plus-box-multiple-outline big-icon"></i>
                                </button>
                            </div>
                        </div>
                        <hr>
                        @if(count($reservations) > 0)
                            @php
                                // Group reservations by check-in date
                                $groupedReservations = $reservations->groupBy(function($reservation) {
                                    return date('d.m.Y', strtotime($reservation->checkin_date));
                                });
                            @endphp

                            @foreach($groupedReservations as $date => $reservationsForDate)
                                <div class="reservations-date-section">
                                    <!-- Display the date -->
                                    <hr class="text-danger m-0 p-0" style="border-width: 3px;">
                                    <h4 class="text-center text-danger fw-bold fs-4 m-0 py-1">{{ $date }}</h4>
                                    <hr class="text-danger m-0 p-0" style="border-width: 3px;">
                                    <div class="items dogItems mt-2">
                                        @foreach($reservationsForDate as $obj)
                                            @if(isset($obj->dog))
                                                <div class="" id="child_{{$obj->dog->id}}" data-res-id="{{$obj->id}}"
                                                     data-position='left'>
                                                    <div class="item card mb-2">
                                                        <div
                                                            class="card-body space-nill bg-extralight bg-success-500 }}">
                                                            <div class="flex justify-between">
                                                                <p>
                                                                    ID{{str_pad($obj->dog->id, 3, '0', STR_PAD_LEFT)}}</p>
                                                                <p>{{$obj->stays}}</p>
                                                            </div>
                                                        </div>
                                                        <div class="card-header portlet-header">
                                                            <div class="flex justify-between align-items-center">
                                                                <div class="flex align-items-center">
                                                                    <a href="uploads/users/dogs/{{$obj->dog->picture}}"
                                                                       target="_blank">
                                                                        <img
                                                                            src="uploads/users/dogs/{{$obj->dog->picture}}"
                                                                            class="img-fluid" alt="Dog"/>
                                                                    </a>
                                                                    <h3 class="name">{{$obj->dog->name}}</h3>
                                                                </div>
                                                                <!-- <div class="flexerf">
                                                                    <button class="no-style text-dark dogInfoBtn"
                                                                            onclick="dogInfo('{{$obj->id}}')">
                                                                        <i class="mdi mdi-information-slab-circle-outline big-icon"></i>
                                                                    </button>
                                                                </div> -->
                                                                <div class="d-flex">
                                                                    <button class="no-style text-dark dogInfoBtn"
                                                                            onclick="dogInfo('{{$obj->id}}')">
                                                                        <i class="mdi mdi-information-slab-circle-outline"></i>
                                                                    </button>
                                                                    <button class="no-style text-danger deleteResCross"
                                                                            onclick="deleteReservation('{{$obj->id}}')">
                                                                        <i class="mdi mdi-close-circle-outline big-icon"></i>
                                                                    </button>
                                                                    <button class="no-style text-dark hiddenInfo"
                                                                            onclick="dogInfo('{{$obj->id}}')">
                                                                        <i class="mdi mdi-information-slab-circle-outline"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <hr class="p-0 m-0">
                                                        <div class="card-body space-nill">
                                                            <p class="pb-1">
                                                                <a href="{{route('admin.customers.preview', ['id'=>$obj->dog->customer->id])}}"
                                                                   target="_blank">{{$obj->dog->customer->name}}
                                                                    ({{$obj->dog->customer->id_number}})</a>
                                                            </p>
                                                            <hr class="p-0 m-0">
                                                            @php
                                                                // Calculate days based on configuration (inclusive or exclusive)
                                                                // Same-day (29-29) always counts as 1 day regardless of mode
                                                                // Inclusive: both checkin and checkout dates count (29-30 = 2 days, 29-31 = 3 days)
                                                                // Exclusive: days between count, same-day is 1 (29-30 = 1 day, 29-31 = 2 days)
                                                                // Normalize dates to start of day for consistent calculation
                                                                $checkinDate = \Carbon\Carbon::parse($obj->checkin_date)->startOfDay();
                                                                $checkoutDate = \Carbon\Carbon::parse($obj->checkout_date)->startOfDay();
                                                                $daysDiff = $checkinDate->diffInDays($checkoutDate);
                                                                
                                                                // Same-day checkin/checkout always counts as 1 day
                                                                if ($daysDiff === 0) {
                                                                    $days_between = 1;
                                                                } else {
                                                                    $calculationMode = config('app.days_calculation_mode', 'inclusive');
                                                                    $days_between = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                                                                }
                                                            @endphp
                                                            <div class="flex justify-between py-2">
                                                                <p class="tag">{{$days_between}} Tage</p>
                                                                @php
                                                                    $balance = $obj->totalAmount ?? 0;
                                                                    $balanceClass = $balance < 0 ? 'text-danger' : ($balance > 0 ? 'text-success' : '');
                                                                    $balanceSign = $balance > 0 ? '+' : '';
                                                                @endphp
                                                                <p class="{{ $balanceClass }}">
                                                                    {{ $balanceSign }}{{ number_format($balance, 2) }}&euro;
                                                                </p>
                                                            </div>
                                                            <hr class="p-0 m-0">
                                                            <div class="flex justify-between py-2 vcheckdates">
                                                                <p>{{date('d.m.Y', strtotime($obj->checkin_date))}}</p>
                                                                <p>-</p>
                                                                <p>{{date('d.m.Y', strtotime($obj->checkout_date))}}</p>
                                                            </div>
                                                        </div>
                                                        <hr class="p-0 m-0">
                                                        <div class="card-body space-nill bg-extralight iconsLists">
                                                            <div class="flex justify-between">
                                                                <ul>
                                                                    <li>
                                                                        <button class="no-style"
                                                                                title="In ein anderes Zimmer verschieben"
                                                                                onclick="moveFromRes('{{$obj->dog->id}}', {{$obj->id}})">
                                                                            <i class="mdi mdi-cursor-move"></i>
                                                                        </button>
                                                                    </li>
                                                                </ul>
                                                                <ul>
                                                                    @if($obj->dog->compatibility == 'V')
                                                                        <li>
                                                                            <button class="no-style" title="V"
                                                                                    data-toggle="tooltip">
                                                                                <i class="fa fa-check fs25"></i>
                                                                            </button>
                                                                        </li>
                                                                    @elseif($obj->dog->compatibility == 'VJ')
                                                                        <li>
                                                                            <button class="no-style" title="VJ"
                                                                                    data-toggle="tooltip">
                                                                                <span class="tbspan nb">nB</span>
                                                                            </button>
                                                                        </li>
                                                                    @elseif($obj->dog->compatibility == 'VM')
                                                                        <li>
                                                                            <button class="no-style" title="VM"
                                                                                    data-toggle="tooltip">
                                                                                <span class="tbspan nm">nM</span>
                                                                            </button>
                                                                        </li>
                                                                    @elseif($obj->dog->compatibility == 'UV')
                                                                        <li>
                                                                            <button class="no-style" title="UV"
                                                                                    data-toggle="tooltip">
                                                                                <i class="fa fa-times text-danger fs25"></i>
                                                                            </button>
                                                                        </li>
                                                                    @elseif($obj->dog->compatibility == 'S')
                                                                        <li>
                                                                            <button class="no-style" title="S"
                                                                                    data-toggle="tooltip">
                                                                                <span class="tbspan s">S</span>
                                                                            </button>
                                                                        </li>
                                                                    @endif
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{----pin modal----}}
            <div class="modal fade" id="pinModal" tabindex="-1" aria-labelledby="pinModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="pinModalLabel">
                                Geben Sie die Geheim-PIN ein</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- PIN Input Field -->
                            <input type="password" id="pin" class="form-control" maxlength="4"
                                   placeholder="Geben Sie die 4-stellige PIN ein"/>
                            <div class="text-danger" id="pinError" style="display: none;">Ungültige PIN, bitte versuchen
                                Sie es erneut.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">schließen
                            </button>

                            <button type="button" class="btn btn-primary" id="submitPin">einreichen</button>
                        </div>
                    </div>
                </div>
            </div>
            {{------end pin modal--------}}

            <div class="col-md-8 col-lg-8 col-xl-8 col-xxl-8">
                <div class="right">
                    <div class="rooms reservationBox scrumboard">
                        @if(count($rooms) > 0)
                            @foreach($rooms as $obj)
                                <div class="room card mb-2" id="parent_{{$obj->id}}"
                                     style="position: relative;z-index: 0;">
                                    <div class="card-header">
                                        <div class="flex justify-between">
                                            <div>
                                                <h2 class="name">
                                                    {{$obj->number}}
                                                </h2>
                                            </div>
                                            <div class="flex align-items-center" style="margin-top: -15px">
                                                <div>
                                                    <span class="badge bg-primary rounded"><span
                                                            class="count">{{count($obj->reservations)}}</span>/{{$obj->capacity}}</span>
                                                </div>
                                                <div>
                                                    <button class="no-style" style="color: #5a5fe0"
                                                            onclick="dogsInRoom('{{$obj->id}}')">
                                                        <i class="mdi mdi-plus-box-multiple-outline big-icon"></i>
                                                    </button>
                                                </div>
                                                {{-- move multiple dogs to another room --}}
                                                <div>
                                                    <button class="no-style" style="color: #5a5fe0"
                                                            data-toggle="tooltip"
                                                            title="In ein anderes Zimmer verschieben"
                                                            onclick="moveMultipleDogs('{{$obj->id}}')">
                                                        <i class="mdi mdi-cursor-move big-icon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body px-1">
                                        <div class="row dogItems rright" style="min-height: 40px;">
                                            @if(count($obj->reservations) > 0)
                                                @foreach($obj->reservations as $item)
                                                    @php
                                                        if(!isset($item->dog))
                                                        {
                                                          continue;
                                                        }
                                                        $__dogjs[$item->dog_id] = $item->dog;
                                                    @endphp
                                                    <div class="col-md-4 col-lg-4 col-xl-4 col-xxl-3 child"
                                                         id="child_{{$item->dog_id}}" data-res-id="{{$item->id}}">
                                                        <div class="item card mb-2 portlet-header" style="z-index:9999">
                                                            @php
                                                                $color = 'bg-warning';
                                                                if($item->color == 'danger')
                                                                {
                                                                  $color = 'bg-danger';
                                                                }elseif($item->color == 'primary')
                                                                {
                                                                  $color = 'bg-primary-500';
                                                                }
                                                            @endphp
                                                            <div
                                                                class="card-body space-nill bg-extralight {{ $color }}">
                                                                <div class="flex justify-between align-items-center">
                                                                    <p>ID{{str_pad($item->dog_id, 3, '0', STR_PAD_LEFT)}}</p>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="vaccination-indicator d-none me-2">
                                                                            <i class="mdi mdi-needle text-danger fs-4"></i>
                                                                        </div>
                                                                        <p class="mb-0">{{$item->stays}}</p>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card-header">
                                                                <div class="flex justify-between align-items-center">
                                                                    <div class="flex align-items-center">
                                                                        <a href="uploads/users/dogs/{{$item->dog->picture}}"
                                                                           target="_blank">
                                                                            <img
                                                                                src="uploads/users/dogs/{{$item->dog->picture}}"
                                                                                class="img-fluid" alt="Dog"/>
                                                                        </a>
                                                                        <div>
                                                                            <h3 class="name">{{$item->dog->name}}</h3>
                                                                        </div>
                                                                    </div>

                                                                    <div class="flexerf">
                                                                        <button class="no-style text-dark dogInfoBtn"
                                                                                onclick="dogInfo('{{$item->id}}')">
                                                                            <i class="mdi mdi-information-slab-circle-outline big-icon"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <hr class="p-0 m-0">
                                                            <div class="card-body space-nill p-0 m-0">
                                                                <div class="flex justify-between pb-2 px-2 py-2">
                                                                    <div>
                                                                        <a href="{{route('admin.customers.preview', ['id' => $item->dog->customer->id])}}" target="_blank">{{$item->dog->customer->name}}
                                                                            ({{$item->dog->customer->id_number}})</a>
                                                                    </div>
                                                                    <div data-toggle="tooltip" title="Saldo">
                                                                        @if($item->totalAmount < 0)
                                                                            <p class="text-danger bjty">
                                                                                {{number_format($item->totalAmount, 2)}}&euro;</p>
                                                                        @elseif($item->totalAmount > 0)
                                                                            <p class="text-success bjty">
                                                                                +{{number_format($item->totalAmount, 2)}}&euro;</p>
                                                                        @else
                                                                            <p class="bjty">
                                                                                {{number_format($item->totalAmount, 2)}}&euro;</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <hr class="p-0 m-0">
                                                                @php
                                                                    // Calculate days based on configuration (inclusive or exclusive)
                                                                    // Same-day (29-29) always counts as 1 day regardless of mode
                                                                    // Inclusive: both checkin and checkout dates count (29-30 = 2 days, 29-31 = 3 days)
                                                                    // Exclusive: days between count, same-day is 1 (29-30 = 1 day, 29-31 = 2 days)
                                                                    // Normalize dates to start of day for consistent calculation
                                                                    $checkinDate = \Carbon\Carbon::parse($item->checkin_date)->startOfDay();
                                                                    $checkoutDate = \Carbon\Carbon::parse($item->checkout_date)->startOfDay();
                                                                    $daysDiff = $checkinDate->diffInDays($checkoutDate);
                                                                    
                                                                    // Same-day checkin/checkout always counts as 1 day
                                                                    if ($daysDiff === 0) {
                                                                        $days_between = 1;
                                                                    } else {
                                                                        $calculationMode = config('app.days_calculation_mode', 'inclusive');
                                                                        $days_between = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                                                                    } 

                                                                    $morning = $item->dog->eating_morning != '' ? '1' : '0';
                                                                    $afternoon = $item->dog->eating_midday != '' ? '1' : '0';
                                                                    $evening = $item->dog->eating_evening != '' ? '1' : '0';
                                                                    $bf = ($item->dog->is_special_eating == 0) ? '' : 'BF ';

                                                                    $eating_habits = $bf . $morning . '-' . $afternoon . '-' . $evening;
                                                                @endphp
                                                                <div class="flex justify-between py-2 px-2">
                                                                    <p class="tag">{{$days_between}} Tage
                                                                        - {{ ($item->dog->neutered == 1) ? 'K' : 'NK' }}</p>
                                                                    <p class="{{ ($bf != '') ? 'bfFormat' : '' }}">{{ $eating_habits }}</p>
                                                                </div>
                                                                <hr class="p-0 m-0">
                                                                <div class="flex justify-between py-2 vcheckdates px-2">
                                                                    <p>{{date('d.m.Y', strtotime($item->checkin_date))}}</p>
                                                                    <p>-</p>
                                                                    <p>{{date('d.m.Y', strtotime($item->checkout_date))}}</p>
                                                                </div>
                                                            </div>
                                                            <hr class="p-0 m-0">
                                                            <div class="card-body space-nill iconsLists">
                                                                <div class="flex justify-between">
                                                                    <ul>
                                                                        <li>
                                                                            <button class="no-style"
                                                                                    data-toggle="tooltip"
                                                                                    title="In ein anderes Zimmer verschieben"
                                                                                    onclick="moveDog('{{$item->dog_id}}', '{{$item->id}}')">
                                                                                <i class="mdi mdi-cursor-move"></i>
                                                                            </button>
                                                                        </li>
                                                                        {{-- @if(isset($item->plan) && $item->plan != null) --}}
                                                                        <li>
                                                                            <button class="no-style"
                                                                                    data-toggle="tooltip" title="Kasse"
                                                                                    onclick="checkoutModal('{{$item->id}}', '{{$item->checkin_date}}', '{{isset($item->plan_id) ? $item->plan_id : false}}','{{ isset($item->plan) ? $item->plan->price : 0 }}')">
                                                                                <i class="mdi mdi-logout-variant"></i>
                                                                            </button>
                                                                        </li>
                                                                        {{-- @endif --}}
                                                                    </ul>
                                                                    <ul>
                                                                        @if($item->dog->water_lover == 1)
                                                                            <li>
                                                                                <button class="no-style"
                                                                                        title="Wassergräber"
                                                                                        data-toggle="tooltip">
                                                                                    <i class="mdi mdi-water-check"></i>
                                                                                </button>
                                                                            </li>
                                                                        @endif

                                                                        @if($item->dog->is_allergy == 1)
                                                                            <li>
                                                                                <button class="no-style"
                                                                                        data-toggle="tooltip"
                                                                                        title="<?php echo $item->dog->allergy; ?>">
                                                                                    <span
                                                                                        style="font-weight: bold; color: green; font-size: 17px; line-height: 1; position: relative; top: 2px;">A</span>
                                                                                </button>
                                                                            </li>
                                                                        @endif

                                                                        @if($item->dog->is_medication == 1)
                                                                            <li>
                                                                                <button class="no-style"
                                                                                        data-toggle="tooltip"
                                                                                        title="Morgen(<?php echo $item->dog->morgen; ?>) -Mittag(<?php echo $item->dog->mittag; ?>)-Abend(<?php echo $item->dog->abend; ?>)">
                                                                                    <i class="mdi mdi-medical-bag"></i>
                                                                                </button>
                                                                            </li>
                                                                        @endif
                                                                        
                                                                        @if($item->dog->compatibility == 'V')
                                                                            <li>
                                                                                <button class="no-style"
                                                                                        style="color: green;" title="V"
                                                                                        data-toggle="tooltip">
                                                                                    <i class="mdi mdi-check-circle"></i>
                                                                                </button>
                                                                            </li>
                                                                        @elseif($item->dog->compatibility == 'UV')
                                                                            <li>
                                                                                <button class="no-style"
                                                                                        style="color: red;" title="UV"
                                                                                        data-toggle="tooltip">
                                                                                    <i class="mdi mdi-close"
                                                                                       style="transform: scale(1.4); display: inline-block;"></i>
                                                                                </button>
                                                                            </li>
                                                                        @elseif($item->dog->compatibility == 'VJ')
                                                                            <li>
                                                                                <button class="no-style" style=""
                                                                                        title="nB"
                                                                                        data-toggle="tooltip">
                                                                                    {{-- <i class="mdi mdi-circle"></i> --}}
                                                                                    <span class="tbspan nb">nB</span>
                                                                                </button>
                                                                            </li>
                                                                        @elseif($item->dog->compatibility == 'VM')
                                                                            <li>
                                                                                <button class="no-style" title="nM"
                                                                                        data-toggle="tooltip">
                                                                                    <span class="tbspan nm">nM</span>
                                                                                </button>
                                                                            </li>
                                                                        @elseif($item->dog->compatibility == 'S')
                                                                            <li>
                                                                                <button class="no-style" title="S"
                                                                                        data-toggle="tooltip">
                                                                                    <span class="tbspan s">S</span>
                                                                                </button>
                                                                            </li>
                                                                        @endif
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- End of Dashboard --}}
    
    {{-- Modal Area Starts Here --}}
    {{-- Add Reservation Modal --}}
    <div class="modal fade" id="addReservation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center">
                        <h3 class="text-center">Reservierung</h3>
                    </div>
                    <hr style="border-color: grey">
                    <form action="{{route('admin.reservation.add')}}" method="POST" enctype="multipart/form-data"
                          id="addReservationForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4 baris">
                                    <select name="initial_dog_id" id="initial_dog_id" class="select2"
                                            data-placeholder='Wählen Sie einen Hund aus' data-live-search="true" required
                                            onchange="loadCustomerDogs(this.value)">
                                        <option selected disabled>Hund auswählen</option>
                                        @foreach($dogs as $dog)
                                            <option value="{{$dog->id}}" data-customer-id="{{$dog->customer_id}}">{{$dog->name}} @if($dog->compatible_breed)
                                                    ({{$dog->compatible_breed}})
                                                @endif - {{isset($dog->customer) ? $dog->customer->name : ''}}
                                                ({{ isset($dog->customer) ? $dog->customer->phone : '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="initial_dog_id">Hund auswählen</label>
                                </div>
                                <input type="hidden" name="is_dashboard" value="1">
                            </div>
                            
                            <!-- Customer Dogs Selection Section -->
                            <div class="col-md-12" id="customerDogsSection" style="display: none;">
                                <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Weitere Hunde des Kunden auswählen</h5>
                                    <small class="text-muted">Wählen Sie alle Hunde aus, die Sie einchecken möchten. Der ursprünglich ausgewählte Hund ist bereits markiert.</small>
                                </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllCustomerDogs()">Alle auswählen</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllCustomerDogs()">Alle abwählen</button>
                                            <span id="customerDogsCount" class="badge bg-primary ms-2">0 ausgewählt</span>
                                        </div>
                                        <div id="customerDogsContainer" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                            <!-- Customer dogs will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" id="rangepicker" name="dates[]"
                                           class="form-control bs-rangepicker-basic" required/>
                                    <label for="rangepicker">Einchecken - Auschecken</label>
                                </div>
                                <input type="hidden" id="flatpickr-range" class="hidden"/>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Speichern</button>
                        <button
                            type="reset"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                            Stornieren
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Reservation Modal --}}
    <div class="modal fade" id="deleteReservation" tabindex="  -1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2 pb-1">Reservierung löschen</h3>
                    </div>
                    <p class="text-center">Sind Sie sicher, dass Sie diese Reservierung löschen möchten?</p>
                    <form class="row g-3" method="POST" action="{{route('admin.reservation.delete')}}">
                        @csrf
                        <div class="col-12 d-flex justify-content-center">
                            <input type="hidden" class="form-control" name="id" id="deleteReservationId"/>
                            <button type="submit" class="btn btn-danger me-sm-3 me-1">Ja, löschen</button>
                            <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                                Abbrechen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Todo Modal --}}
    <div class="modal fade" id="addTodo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center">
                        <h3 class="text-center">Neue Aufgabe hinzufügen</h3>
                    </div>
                    <hr>
                    <form class="row g-3" method="POST" action="{{route('admin.todo.add')}}">
                        @csrf
                        <div class="col-md-12">
                            <div class="form-floating form-floating-outline">
                                <select id="todos" class="select2 form-select" onchange="previewTask(this.value)">
                                    <option selected disabled>Wählen</option>
                                    @foreach ($tasks as $obj)
                                        <option value="{{$obj->title}}">{{$obj->title}}</option>
                                    @endforeach
                                </select>
                                <label for="todos">Wählen Sie Aufgabe aus</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-floating form-floating-outline mb-2">
                                <textarea required class="form-control" name="task" id="task"
                                          style="height: 90px"></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <input type="hidden" name="date" id="date"/>
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Speichern</button>
                            <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                                Stornieren
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Todo Modal --}}
    <div class="modal fade" id="deleteTodo" tabindex="  -1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h3 class="mb-2 pb-1">Aufgabe löschen</h3>
                    </div>
                    <p class="text-center">Sind Sie sicher, dass Sie diese Aufgabe löschen möchten?</p>
                    <form class="row g-3" method="POST" action="{{route('admin.todo.delete')}}">
                        @csrf
                        <div class="col-12 d-flex justify-content-center">
                            <input type="hidden" class="form-control" name="id" id="deleteTodoId"/>
                            <button type="submit" class="btn btn-danger me-sm-3 me-1">Ja, löschen</button>
                            <button
                                type="reset"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                                Stornieren
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Employee Attendence Model --}}
    <div class="modal fade" id="EmployeeAttendenceModal" tabindex="-1" aria-labelledby="usersModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="usersModalLabel">Alle Mitarbeiter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive text-nowrap">
                        <table class="table" id="myTable">
                            <thead class="table-light">
                            <tr>
                                <th>Bild</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Schicht</th>
                                <th class="text-center">Aktion</th>
                            </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                            @foreach($users->where('id', '!=', 1) as $obj)
                                <tr>
                                    <td>
                                        @if ($obj->picture)
                                            <img src="uploads/users/{{$obj->picture}}" width="50" height="50"
                                                 style="object-fit: cover" alt="#">
                                        @endif
                                    </td>
                                    <td>{{$obj->id}}</td>
                                    <td>{{$obj->name}}</td>
                                    <td>{{$obj->email}}</td>
                                    <td>
                                        @if($obj->todays_working_hours)
                                            <i>Heute gearbeitet: <strong>{{$obj->todays_working_hours}}</strong></i>
                                        @endif
                                        @forelse($obj->todays_shifts as $shift)
                                            <div>
                                                <strong>{{ \Carbon\Carbon::parse($shift->start)->format('H:i') }}</strong>
                                                -
                                                <strong>{{ \Carbon\Carbon::parse($shift->end)->format('H:i') }}</strong>
                                            </div>
                                            @empty
                                        @endforelse
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end">
                                            <div>
                                                <a href="javascript:void(0);">
                                                    <button id="bin_da_{{ $obj->id }}" class="btn btn-success btn-sm"
                                                            onclick="createEventRecord({{ $obj->id }})">
                                                        Bin Da
                                                    </button>
                                                </a>

                                                <a href="javascript:void(0);">
                                                    <button id="bin_fort_{{ $obj->id }}" class="btn btn-danger btn-sm"
                                                            onclick="endEventRecord({{ $obj->id }})" disabled>
                                                        Bin Fort
                                                    </button>
                                                </a>

                                                <a href="/admin/employeetrack/monatsplan">
                                                    <button id="monatsplan_button" class="btn btn-info btn-sm">
                                                        Monatsplan
                                                    </button>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Dog in Room Modal --}}
    <div class="modal fade" id="dogsInRoom" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered modal-lg">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center">
                        <h3 class="text-center">Hund hinzufügen</h3>
                    </div>
                    <hr style="border-color: grey">
                    <form action="{{route('admin.reservation.dashboard')}}" method="POST" enctype="multipart/form-data"
                          id="addReservationForm2">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4 baris">
                                    <select name="dog_id" id="id_dog" class="select2" data-live-search="true" required
                                            onchange="searchDogPlan(this.value)">
                                        <option selected disabled>Hund auswählen</option>
                                        @foreach($dogs as $dog)
                                            <option value="{{$dog->id}}">{{$dog->name}} @if($dog->compatible_breed)
                                                    ({{$dog->compatible_breed}})
                                                @endif - {{isset($dog->customer) ? $dog->customer->name : ''}}
                                                ({{isset($dog->customer) ? $dog->customer->phone : ''}})
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="id_dog">Hund</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="plan_id" id="plan_id" class="select2" data-live-search="true" required
                                            autofocus>
                                        <option selected disabled>Wählen Sie Preispläne</option>
                                        @foreach($plans as $obj)
                                            <option value="{{$obj->id}}"><?php echo $obj->title; ?></option>
                                        @endforeach
                                    </select>
                                    <label for="plan_id">Preispläne</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" id="rangepicker" name="dates[]"
                                           class="form-control bs-rangepicker-basic" required/>
                                    <label for="rangepicker">Einchecken - Auschecken</label>
                                </div>
                                <input type="hidden" id="flatpickr-range" class="hidden"/>
                                <input type="hidden" name="room_id" id="dogsInRoomId" class="form-control">
                                <input type="hidden" name="is_dashboard" value="1">

                            </div>
                        </div>
                        <button type="submit" id="submitBtn2" class="btn btn-primary">Speichern</button>
                        <button
                            type="reset"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                            Stornieren
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Move Multiple Dogs Modal --}}
    <div class="modal fade" id="moveMultipleDogsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered modal-lg">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center">
                        <h3 class="text-center">Mehrere Hunde verschieben</h3>
                    </div>
                    <hr style="border-color: grey">
                    <form action="{{route('admin.move.multiple.dogs')}}" method="POST" id="moveMultipleDogsForm">
                        @csrf
                        <input type="hidden" name="source_room_id" id="source_room_id">
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Hunde auswählen:</h5>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllDogs()">Alle auswählen</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllDogs()">Alle abwählen</button>
                                    </div>
                                    <span id="selectedCount" class="badge bg-primary">0 ausgewählt</span>
                                </div>
                                <div id="dogsCheckboxContainer" class="mb-4" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                    <!-- Dogs will be loaded here via JavaScript -->
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="target_room_id" id="target_room_id" class="select2" required>
                                        <option selected disabled>Zielzimmer auswählen</option>
                                        @foreach($rooms as $room)
                                            <option value="{{$room->id}}">{{$room->number}} ({{$room->type}}) - Kapazität: {{$room->capacity}}</option>
                                        @endforeach
                                    </select>
                                    <label for="target_room_id">Zielzimmer</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Abbrechen
                            </button>
                            <button type="submit" class="btn btn-primary" id="moveMultipleDogsBtn">
                                Hunde verschieben
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{----Plan model---}}
    <div id="planmodel2" class="custom-plan-modal d-none">
        <div class="modal-content p-3 p-md-5">
            <div class="modal-body p-md-0">
                <button type="button" class="btn-clos no-style f-right" onclick="closePlanModal()">
                    <i class="fa fa-close fa-xl"></i>
                </button>


                <div class="text-center">
                    <h3 class="text-center">Zustand der Zimmer</h3>
                </div>
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-danger fs-4" id="resetButton"
                            style="padding: 6px 12px; border-radius: 5px;">
                        Neue Seite
                    </button>
                </div>
                <hr>
                <form action="#" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <ol id="selectablePlanModel2">
                                @foreach($rooms as $room)
                                    <li class="ui-widget-content fs-4"
                                        data-id="{{ $room->id }}"
                                        data-condition="{{ $room->room_condition }}"
                                        style="
                                  background-color: {{ $room->room_condition == 1 ? 'green' : ($room->room_condition == 2 ? 'red' : 'white') }};
                                  color: {{ $room->room_condition == 0 ? 'black' : 'white' }};
                              ">
                                        {{ $room->number }}
                                    </li>
                                @endforeach
                            </ol>
                            <input type="hidden" name="room_id" id="selectedRoomPlanModel2">
                            <input type="hidden" name="dog_id" id="dogIdPlanModel2">
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Change Dog Room Modal --}}
    <div class="modal fade" id="moveDog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-top modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center">
                        <h3 class="text-center">In ein anderes Zimmer verschieben</h3>
                    </div>
                    <hr>
                    <form id="moveDogForm" action="{{route('admin.move.dog')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                {{-- <div class="form-floating form-floating-outline mb-4">
                                    <select name="room_id" id="id_room" class="form-control" data-live-search="true" required>
                                      <option selected disabled>Zimmer auswählen</option>
                                      @foreach($rooms as $room)
                                          <option value="{{$room->id}}">{{$room->number}}</option>
                                      @endforeach
                                    </select>
                                    <label for="id_room">Zimmer</label>
                                </div> --}}
                                <ol id="selectableMoveDog">
                                    @foreach($rooms as $room)
                                        <li class="ui-widget-content-2" data-id="{{$room->id}}">{{$room->number}}</li>
                                    @endforeach
                                </ol>
                                <input type="hidden" name="room_id" id="selectedRoomMoveDog">
                                <input type="hidden" name="dog_id" id="dogIdMoveDog">
                                <input type="hidden" name="res_id" id="resIdMoveDog"/>
                            </div>
                        </div>
                        <hr>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Speichern</button>
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                                Stornieren
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Dog Information Modal --}}
    <div class="modal fade" id="dogInfo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content p-3 p-md-5" style="overflow: hidden">
                <div class="modal-body p-md-0" style="overflow-y: auto; overflow-x: hidden; max-height: 100vh; scrollbar-width: none; -ms-overflow-style: none;">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center">
                            <div>
                                <img id="dogPicture" src="" width="120" class="rounded img-fluid" alt="Dog Picture"/>
                            </div>
                            <div>
                                <span id="dog_name" class="fs-3 fw-bold text-black ms-3"></span>
                                <span id="separator" class="fs-3 fw-bold text-black ms-3"
                                      style="display: none;">-</span>
                                {{-- <span  class="fs-3 fw-bold text-black ms-3">-</span> --}}
                                <span class="fs-3 fw-bold text-black ms-3"> </span>
                                <span id="allergy" style="font-size: 24px; font-weight: bold; color: #39FF14;"></span>
                                <br><span class="text-black fs-4 fw-bolder ms-3" id="dog_breed"></span>
                            </div>
                        </div>
                        <button type="button" class="close no-style" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-times big-icon"></i>
                        </button>
                    </div>
                    <hr/>

                    <div class="row" style="margin: 0; width: 100%;">

                        <div class="col-md-8" style="padding-right: 15px; max-width: 100%;">
                            <table class="table table-striped text-cente infoModalHabitsTable"
                                   style="width: 100%; margin-left:0; table-layout: fixed;">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th><span style=" color:#0053ac">Morgen</span></th>
                                    <th><span style=" color:#0053ac">Mittag</span></th>
                                    <th><span style=" color:#0053ac">Abend</span></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <th><span style=" color:#0053ac">Medikamente</span></th>
                                    <td id="medication_morning"></td>
                                    <td id="medication_midday"></td>
                                    <td id="medication_evening"></td>
                                </tr>

                                <tr>
                                    <th><span style=" color:#0053ac">Essgewohnheiten</span></th>
                                    <td id="eating_morning"></td>
                                    <td id="eating_midday"></td>
                                    <td id="eating_evening"></td>

                                </tr>
                                <tr>
                                    <th><span id="bf_span" style=" color:#0053ac">Besondere Fütterung</span></th>
                                    <td id="special_morning"></td>
                                    <td id="special_midday"></td>
                                    <td id="special_evening"></td>
                                </tr>
                                </tbody>
                            </table>
                            <h4 class="text-start mx-3 mt-3 mb-4">Kunde: <span id="customer_name" class="fs-4"
                                                                               style="text-decoration: underline; color:#0053ac"></span>
                            </h4>
                            <div class="table-responsiv">
                                <table class="table table-striped dogInfoTable">
                                    <tbody>
                                    <tr>
                                        <th>Dog ID: <span id="dog_id"></span></th>
                                        <th>Geschlecht: <span id="gender"></span></th>
                                    </tr>
                                    <tr>
                                        <th>Tagestarif: <span id="plan_price"></span></th>
                                        <th>Pensionstarif: <span id="plan_title"></span></th>
                                    </tr>
                                    <tr>
                                        <th>Typ: <span id="plan_type"></span></th>
                                        <th>Kastriert Ja/Nein: <span id="kastrated"></span></th>
                                    </tr>
                                    <tr>
                                        <th>Alter: <span id="age"></span></th>
                                        <th>Chipnummer: <span id="chipnumber"></span></th>
                                    </tr>

                                    {{-- <tr>
                                      <th>Rabatt: <span id="plan_discount"></span></th>
                                    </tr> --}}
                                    {{--
                                    <tr>
                                      <th>Rasse: <span id="breed"></span></th>
                                      <th>Gewicht: <span id="weight"></span></th>
                                    </tr>
                                    <tr>
                                      <th>Medikamente: <span id="medicated"></span></th>
                                    </tr>
                                    <tr>
                                      <th>Gesundheitsprobleme: <span id="is_problem"></span></th>
                                      <th>Essgewohnheiten: <span id="routine"></span> (<span id="bfinfo"></span> )</th>
                                    </tr> --}}
                                    </tbody>
                                </table>
                            </div>

                            <hr style="width: 100%; height: 2px; background-color: grey;">
                            
                            <!-- Vaccinations Section -->
                            <h4 class="text-start">Impfungen</h4>
                            <div class="mb-3">
                                <form id="vaccinationForm" class="vaccination-form py-2 mb-3" style="width:100%">
                                    @csrf
                                    <input type="hidden" id="vaccination_dog_id" name="dog_id">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label mb-1">Impfstoff Name</label>
                                            <input type="text" class="form-control" id="vaccine_name" name="vaccine_name" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Impfdatum</label>
                                            <input type="date" class="form-control" id="vaccination_date" name="vaccination_date" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Nächste Impfung</label>
                                            <input type="date" class="form-control" id="next_vaccination_date" name="next_vaccination_date" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">Hinzufügen</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <p id="vaccination_saved" class="text-success hidden">
                                <i class="far fa-check-circle"></i>
                                Impfung erfolgreich gespeichert
                            </p>
                            <p id="vaccination_deleted" class="text-success hidden">
                                <i class="far fa-check-circle"></i>
                                Impfung erfolgreich gelöscht
                            </p>
                            <p id="vaccination_updated" class="text-success hidden">
                                <i class="far fa-check-circle"></i>
                                Impfstatus erfolgreich aktualisiert
                            </p>
                            <div class="table-responsive">
                                <table class="table table-striped dogInfoTable" id="vaccinationsTable">
                                    <thead>
                                    <tr>
                                        <th>Impfstoffname</th>
                                        <th>Impfdatum</th>
                                        <th>Nächste Impfung</th>
                                        <th>Status</th>
                                        <th>Aktion</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center">Keine Impfungen gefunden</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <hr style="width: 100%; height: 2px; background-color: grey;">

                            <h4 class="text-start">Hunde Freunde</h4>
                            <div class="table-responsive">
                                <table class="table table-striped dogInfoTable" id="hundeFriends">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Name</th>
                                        <th>Freund seitdem</th>
                                        <th>Aktion</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>
                                            <img
                                                src="uploads/users/dogs/1714434169360_F_602743936_qbTuk7bb34cSYBgSDbsirlmJSbxRBUFM.jpg"
                                                width="40" height="40" class="rounded img-fluid" alt="Friend Picture"/>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <button class="no-style text-danger">
                                                <i class="far fa-times-circle"></i> Remove
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4" style="padding-left: 15px; max-width: 100%;">
                            {{-- Checkin/Checkout --}}
                            <div class="form-floating form-floating-outline mb-2">
                                <input type="text" id="rangepicker" name="dates[]"
                                       class="form-control bludate bs-rangepicker-basic" required/>
                                <label for="rangepicker">Einchecken - Auschecken</label>
                            </div>
                            <input type="hidden" id="flatpickr-range" class="hidden"/>
                            <p id="reso_saved" class="text-success hidden">
                                <i class="far fa-check-circle"></i>
                                Check-in-/Check-out-Datum erfolgreich aktualisiert
                            </p>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" id="resDateUpdate">Aktualisieren</button>
                            </div>
                            {{-- Notes Area --}}
                            <h3 class="mb-0">Notiz</h3>
                            <textarea class="form-control" name="note" id="note" cols="5" rows="5"></textarea>
                            <p id="note_saved" class="text-success hidden">
                                <i class="far fa-check-circle"></i>
                                Hinweis erfolgreich aktualisiert
                            </p>
                            <input type="hidden" name="id" id="note_id"/>
                            <input type="hidden" name="res_id" id="res_id"/>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary mt-2" id="submitNote">Aktualisieren
                                </button>
                            </div>
                            
                            {{-- Visit History Section --}}
                            <h4 class="text-start mt-4">Besuchsverlauf</h4>
                            <div class="table-responsive">
                                <table class="table table-striped dogInfoTable" id="visitHistory">
                                    <thead>
                                    <tr>
                                        <th>Zeitraum</th>
                                        <th>Tage</th>
                                        <th>Preis</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Visit history will be populated here -->
                                    </tbody>
                                    <tfoot>
                                    <tr class="fw-bold">
                                        <td>Gesamt:</td>
                                        <td id="totalDays">0</td>
                                        <td id="totalAmount">€0.00</td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Dog Documents Section --}}
                            <h4 class="text-start mt-4">Dokumente</h4>
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                                    <i class="mdi mdi-plus"></i> Dokument hinzufügen
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped dogInfoTable" id="documentsTable">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="text-end">Aktionen</th>
                                    </tr>
                                    </thead>
                                    <tbody id="documentsTableBody">
                                    <!-- Documents will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Checkout Modal --}}
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-lg modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center">
                        <h3 class="text-center">Kasse</h3>
                    </div>
                    <hr>
                    <form action="{{route('admin.reservation.checkout')}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <th>Einchecken</th>
                                        <td id="checkin" class="text-primary"></td>
                                        <th>Auschecken</th>
                                        <td id="checkout" class="text-primary"></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="price_plan" id="price_plan" class="select2" data-live-search="true"
                                            required>
                                        @foreach($plans as $obj)
                                            <option value="{{$obj->id}}"><?php echo $obj->title; ?></option>
                                        @endforeach
                                    </select>
                                    <label for="price_plan">Preisplan</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="gateway" id="gateway" class="select2" data-live-search="true"
                                            required>
                                        <option value="Bar">Bar</option>
                                        <option value="Bank">Banküberweisung</option>
                                    </select>
                                    <label for="gateway">Zahlungsart</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="hidden" name="status" id="status_value" value="1">
                                    <select id="status_display" class="select2" data-live-search="true" disabled>
                                        <option value="1">Bezahlt</option>
                                        <option value="0">Nicht bezahlt</option>
                                        <option value="2">Offen</option>
                                    </select>
                                    <label for="status_display">Status</label>
                                </div>
                            </div>
                            <div class="col-md-12" id="discountSection">
                                <div class="d-flex justify-space-between">
                                    <div class="">
                                        <label>Rabatt</label>
                                    </div>
                                    <div class="form-check ms-5">
                                        <input name="discount" class="form-check-input" type="radio" value="0"
                                               id="discount1" checked/>
                                        <label class="form-check-label" for="discount1"> 0% </label>
                                    </div>
                                    <div class="form-check mx-4">
                                        <input name="discount" class="form-check-input" type="radio" value="10"
                                               id="discount2"/>
                                        <label class="form-check-label" for="discount2"> 10% </label>
                                    </div>
                                    <div class="form-check">
                                        <input name="discount" class="form-check-input" type="radio" value="15"
                                               id="discount3"/>
                                        <label class="form-check-label" for="discount3"> 15% </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" name="plan_cost" step="0.01" id="plan_cost"
                                            class="form-control text-primary" value="0.00" readonly/>
                                    <label for="plan_cost">Planpreis (&euro;)</label>
                                </div>
                                <div class="text-end mb-2">
                                    <button type="button" id="resetPlanPriceBtn" class="btn btn-sm btn-outline-secondary" style="display:none;">
                                        Planpreis zurücksetzen
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" name="special_cost" step="0.01" id="special_cost"
                                            class="form-control text-primary absInput" value="0.00"/>
                                    <label for="special_cost">Zusätzliche Kosten (&euro;)</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" name="total" step="0.01" id="total_amount"
                                            class="form-control text-primary absInput" value="0.00"/>
                                    <label for="total_amount">Rechnungsbetrag (&euro;)</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-4" id="vatBreakdown">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" style="background-color: #f8f9fa;">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 33.33%;">Netto</th>
                                                <th class="text-center" style="width: 33.33%;">MwSt (<span id="vat_percentage_display">20</span>%)</th>
                                                <th class="text-center" style="width: 33.33%;">Brutto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center"><span id="vat_net_amount" class="fw-bold">0.00€</span></td>
                                                <td class="text-center"><span id="vat_amount" class="fw-bold">0.00€</span></td>
                                                <td class="text-center"><span id="vat_gross_amount" class="fw-bold text-primary">0.00€</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" name="received_amount" step="0.01" id="received_amount"
                                            class="form-control text-primary absInput" value="0.00"/>
                                    <label for="received_amount">Betrag Erhalten (&euro;)</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" name="remaining_amount" step="0.01" id="remaining_amount"
                                            class="form-control text-primary" value="0.00" readonly/>
                                    <label for="remaining_amount">Restbetrag (&euro;)</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p style="font-size: 17px; margin: 0;">
                                            <strong>Kundenguthaben: </strong> <span id="saldo">0.00€</span>
                                        </p>
                                    </div>
                                    <div id="walletCheckboxContainer" style="display: none;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="use_wallet" id="use_wallet" value="1">
                                            <label class="form-check-label" for="use_wallet">
                                                Guthaben verwenden (<span id="wallet_available">0.00€</span>)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mb-4" id="walletBreakdown" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>Aufschlüsselung:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Guthaben verwendet: <span id="wallet_used_display">0.00€</span></li>
                                        <li>Barzahlung: <span id="cash_payment_display">0.00€</span></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-12 mb-4" id="hellocashSection">
                                <div class="card border-primary" style="background-color: #f8f9fa;">
                                    <div class="card-body p-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="send_to_hellocash" id="send_to_hellocash" value="1">
                                            <label class="form-check-label" for="send_to_hellocash">
                                                <strong>An Registrierkasse senden</strong> <span class="text-muted">(Kassensystem)</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="id" id="checkoutId"/>
                        <input type="hidden" name="days" id="days"/>
                        <input type="hidden" name="checkout" id="checkout_date"/>
                        <button type="button" class="btn btn-primary" id="checkoutSubmitBtn" onclick="submitCheckoutForm()">Kassa</button>
                        <button
                            type="reset"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                            Stornieren
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- HelloCash Confirmation Modal --}}
    <div class="modal fade" id="hellocashConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrierkasse Bestätigung</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Möchten Sie diese Rechnung wirklich an die Registrierkasse senden?</strong></p>
                    <div class="alert alert-warning">
                        <strong>Wichtig:</strong> Nach dem Senden an die Registrierkasse wird die Rechnung an die Steuerbehörde übermittelt. 
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                    <div class="mb-3">
                        <strong>Rechnungsbetrag:</strong> <span id="confirmInvoiceAmount" class="fw-bold fs-5 text-primary">0.00€</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-primary" id="confirmHelloCashBtn">Ja, an Registrierkasse senden</button>
                </div>
            </div>
        </div>
    </div>

    {{-- FriendshipModal --}}
    <div class="modal fade" id="friendshipModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center">
                        <h3 class="text-center">
                            Zu Freunden machen?
                        </h3>
                    </div>
                    <hr>
                    <form action="{{route('admin.move.friendship')}}" method="POST">
                        @csrf
                        <p>
                            Sind Sie sicher, dass Sie alle diese Hunde als Freunde markieren möchten?
                        </p>
                        <input type="hidden" name="dog_id" id="dog_id">
                        <input type="hidden" name="room_id" id="room_id">
                        <button type="submit" class="btn btn-primary">Ja</button>
                        <button
                            type="reset"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                            Nein
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Add/Edit Document Modal --}}
    <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentModalTitle">Dokument hinzufügen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="documentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="documentDogId" name="dog_id">
                        
                        <div class="mb-3">
                            <label for="documentName" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="documentName" name="name" required>
                        </div>
                        
                        <div class="mb-3" id="documentFileField">
                            <label for="documentFile" class="form-label">Datei <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="documentFile" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                            <small class="text-muted">Max. 10MB. Erlaubte Formate: PDF, DOC, DOCX, JPG, PNG, TXT</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsModalLabel">Ereignisdetails</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Startzeit:</strong> <span id="eventStartTime"></span></p> 
                    <p><strong>Endzeit:</strong> <span id="eventEndTime"></span></p> 
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>
    {{-- End of Modal Area --}}
    
@endsection
@section('extra_js')
    {{-- Script Area Starts Here --}}
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"
            integrity="sha256-sw0iNNXmOJbQhYFuC9OF2kOlD5KQKe1y5lfBn4C9Sjg=" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/flatpickr/flatpickr.js"></script>
    <script src="assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="assets/vendor/libs/jquery-timepicker/jquery-timepicker.js"></script>
    <script src="assets/vendor/libs/pickr/pickr.js"></script>
    <script src="assets/js/forms-pickers.js"></script>
    <script>
        
        // Plan Modal
        document.addEventListener('DOMContentLoaded', function () {
            // Use the unique ID for the `planmodel2` modal
            const roomElements = document.querySelectorAll('#selectablePlanModel2 li');

            roomElements.forEach(room => {
                room.addEventListener('click', function () {
                    const roomId = this.getAttribute('data-id');
                    let currentCondition = parseInt(this.getAttribute('data-condition')) || 0;
                    // Determine the next condition (0 -> 1 -> 2 -> 0)
                    const newCondition = (currentCondition + 1) % 3;

                    // Send AJAX request to update room_condition
                    fetch("{{ route('admin.rooms.updateCondition') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({room_id: roomId, room_condition: newCondition})
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                this.setAttribute('data-condition', data.room_condition);
                                if (data.room_condition == 1) {
                                    this.style.backgroundColor = 'green';
                                    this.style.color = 'white';
                                } else if (data.room_condition == 2) {
                                    this.style.backgroundColor = 'red';
                                    this.style.color = 'white';
                                } else {
                                    this.style.backgroundColor = 'transparent';
                                    this.style.color = 'black';
                                }
                            } else {
                                console.error('Error:', data.message);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });
        });

        //Room Reset Modal
        document.getElementById('resetButton').addEventListener('click', function () {
            // Confirm the reset action
            if (!confirm('Bist du sicher, dass du zurücksetzen möchtest?')) {
                return false;
            }

            // Use the unique ID for the `planmodel2` modal
            const rooms = document.querySelectorAll('#selectablePlanModel2 li');

            rooms.forEach(room => {
                room.style.backgroundColor = 'white';
                room.style.color = 'black';
                room.setAttribute('data-condition', '0');
            });

            // Send an AJAX request to reset room_condition in the database
            fetch("{{ route('admin.reset.roomcondition') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    // Optionally send any other data here, if needed
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Room conditions reset successfully');
                    } else {
                        console.error('Failed to reset room conditions');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });

        // Handle PIN submit
        document.getElementById('submitPin').addEventListener('click', function () {
            const enteredPin = document.getElementById('pin').value;

            $.ajax({
                url: '{{ route("admin.validate.pin") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    pin: enteredPin
                },
                success: function (response) {
                    if (response.success) {

                        $('#pinModal').modal('hide');
                        location.reload()

                    } else {

                        document.getElementById('pinError').style.display = 'block';
                    }
                }
            });
        });

        //logout session for sidebar

        $('#logoutsessionbutton').click(function () {
            $.ajax({
                url: '{{ route("admin.logout.session") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function (response) {

                    window.location.href = '/admin/dashboard';
                },
                error: function (xhr, status, error) {
                    console.error('Logout failed:', error);
                }
            });
        });

        var dogs = @json($__dogjs);
        var rooms = @json($rooms);
        var pricePlans = @json($plans);

        function reverseDate() {
            var dateText = $("#date_heading").text();
            var parts = dateText.split("."); // Split the date string into an array [day, month, year]
            var date = new Date(parts[2], parts[1] - 1, parts[0]); // Create a Date object (year, monthIndex, day)
            date.setDate(date.getDate() - 1); // Subtract one day

            // Format the date back to the desired format (dd/mm/yyyy)
            var formattedDate = `${date.getDate().toString().padStart(2, '0')}.${(date.getMonth() + 1).toString().padStart(2, '0')}.${date.getFullYear()}`;
            // Update the text content of the h6 element with the formatted date
            $("#date_heading").text(formattedDate);

            // Fetch Todos for this date
            fetchTodos();
        }

        function forwardDate() {
            var dateText = $("#date_heading").text();
            var parts = dateText.split("."); // Split the date string into an array [day, month, year]
            var date = new Date(parts[2], parts[1] - 1, parts[0]); // Create a Date object (year, monthIndex, day)
            date.setDate(date.getDate() + 1); // Add one day

            // Format the date back to the desired format (dd/mm/yyyy)
            var formattedDate = `${date.getDate().toString().padStart(2, '0')}.${(date.getMonth() + 1).toString().padStart(2, '0')}.${date.getFullYear()}`;
            // Update the text content of the h6 element with the formatted date
            $("#date_heading").text(formattedDate);

            // Fetch Todos for this date
            fetchTodos();

        }

        function toggleDateInput() {
            var text = $("#date_heading").text();
            var sp = text.split('.');
            var date = sp[2] + '-' + sp[1] + '-' + sp[0];
            $("#date_heading").addClass('hidden');
            $("#date_input").val(date);
            $("#date_input").removeClass('hidden');
        }

        function dateInputChange() {
            var text = $("#date_input").val();
            var sp = text.split('-');
            var date = sp[2] + '.' + sp[1] + '.' + sp[0];
            $("#date_heading").text(date);
            $("#date_heading").removeClass('hidden');
            $("#date_input").addClass('hidden');

            // Fetch Todos for this date
            fetchTodos();
        }

        function addReservation() {
            $("#addReservation").modal('show');
        }

        $("#addReservationForm").on("submit", function (e) {
            var submitButton = $("#submitBtn");

            submitButton.prop("disabled", true);

            submitButton.text("Speichern...");
        });

        $("#addReservationForm2").on("submit", function (e) {
            var submitButton = $("#submitBtn2");

            submitButton.prop("disabled", true);

            submitButton.text("Speichern...");
        });

        function showEmployeeDetails(id, name, email, username, phone, address, city, country, status) {
            // Populate modal with employee data
            $('#employeeName').text(name);
            $('#employeeEmail').text(email);
            $('#employeeUsername').text(username);
            $('#employeePhone').text(phone);
            $('#employeeAddress').text(address);
            $('#employeeCity').text(city);
            $('#employeeCountry').text(country);

            // Display the status as Active or Inactive
            var statusText = (status == 1) ? 'Aktiv' : 'Inaktiv';
            $('#employeeStatus').text(statusText);

            // Show the modal
            $('#employeeModal').modal('show');
        }

        function deleteReservation(id) {
            $("#deleteReservation #deleteReservationId").val(id);
            $("#deleteReservation").modal('show');
        }

        function createEventRecord(employeeId) {
            $.ajax({
                url: '{{ route("admin.employees.create_event") }}',
                type: 'POST',
                data: {
                    id: employeeId,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.message);

                        $(`#bin_da_${employeeId}`).prop('disabled', true);
                        $(`#bin_fort_${employeeId}`).prop('disabled', false);

                        if (response.user.picture) {
                            const profilePicElement = `
                        <div id="profile_pic_${employeeId}" class="me-2">
                            <img src="${response.user.picture}"
                                class="rounded-circle cursor-pointer"
                                title="${response.user.name}"
                                data-toggle="tooltip"
                                style="width: 50px; height: 50px; border: 1px solid #007bff;">
                        </div>`;

                            $('#profilePicturesContainer').append(profilePicElement);
                        } else {
                            const firstLetter = response.user.name.charAt(0).toUpperCase();
                            const nameElement = `
                        <div id="profile_pic_${employeeId}" class="me-2 d-flex justify-content-center align-items-center"
                            style="width: 50px; height: 50px; border: 1px solid #007bff; border-radius: 50%; background-color: #007bff; color: white;">
                            <span>${firstLetter}</span>
                        </div>`;

                            $('#profilePicturesContainer').append(nameElement);
                        }
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('Ein Fehler ist beim Erstellen des Ereignisses aufgetreten.');
                }
            });
        }

        function fetchEventStatuses() {
            $.ajax({
                url: '{{ route("admin.employees.check_event_status") }}',
                type: 'GET',
                success: function (response) {
                    response.forEach(eventStatus => {
                        const {uid, status, user} = eventStatus;

                        if (status === 'none') {
                            $(`#bin_da_${uid}`).prop('disabled', false);
                            $(`#bin_fort_${uid}`).prop('disabled', true);
                        } else if (status === 'ongoing') {
                            $(`#bin_da_${uid}`).prop('disabled', true);
                            $(`#bin_fort_${uid}`).prop('disabled', false);
                        } else if (status === 'completed') {
                            $(`#bin_da_${uid}`).prop('disabled', false);
                            $(`#bin_fort_${uid}`).prop('disabled', true);
                        }

                        if (status === 'ongoing') {
                            if (user.picture) {
                                const profilePicElement = `
                            <div id="profile_pic_${uid}" class="me-2">
                                <img src="${user.picture}"
                                    class="rounded-circle cursor-pointer"
                                    title="${user.name}"
                                    data-toggle="tooltip"
                                    style="width: 50px; height: 50px; border: 1px solid #007bff;">
                            </div>`;

                                // Remove any existing profile pic and append the new one
                                $(`#profile_pic_${uid}`).remove();
                                $('#profilePicturesContainer').append(profilePicElement);
                            } else {
                                const firstLetter = user.name.charAt(0).toUpperCase();
                                const nameElement = `
                            <div id="profile_pic_${uid}" class="me-2 d-flex justify-content-center align-items-center"
                                style="width: 50px; height: 50px; border: 1px solid #007bff; border-radius: 50%; background-color: #007bff; color: white;cursor:pointer;">
                                <span>${firstLetter}</span>
                            </div>`;

                                // Remove any existing profile pic and append the new one
                                $(`#profile_pic_${uid}`).remove();
                                $('#profilePicturesContainer').append(nameElement);
                            }
                        } else {
                            $(`#profile_pic_${uid}`).remove();
                        }
                    });
                },
                error: function () {
                    console.error('Error fetching event statuses.');
                }
            });
        }

        function endEventRecord(employeeId) {
            $.ajax({
                url: '{{ route("admin.employees.end_event") }}',
                type: 'POST',
                data: {
                    id: employeeId,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.message); // "Event ended successfully."

                        $(`#bin_da_${employeeId}`).prop('disabled', false);
                        $(`#bin_fort_${employeeId}`).prop('disabled', true);
                        $(`#profile_pic_${employeeId}`).remove();

                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('Ein Fehler ist beim Beenden des Ereignisses aufgetreten.');
                }
            });
        }

        function addTodo() {
            var date = $("#date_heading").html();
            var parts = date.split('.');
            date = parts[1] + '/' + parts[0] + '/' + parts[2];

            $("#addTodo #date").val(date);
            $("#addTodo").modal('show');
        }

        function previewTask(task) {
            $("#task").val(task);

        }

        function deleteTodo(id) {
            $("#deleteTodo #deleteTodoId").val(id);
            $("#deleteTodo").modal('show');
        }


        function invertTodoStatus(route, method, token, todoId) {
            $.ajax({
                url: route,
                method: method,
                data: {_token: token, todoId: todoId},
                success: function (response) {
                    // Fetch Updated Todos
                    fetchTodos();
                },
                error: function (xhr, status, error) {
                    // Handle errors
                    console.error(error);
                }
            });

        }

        function dogsInRoom(id) {
            $("#dogsInRoom #dogsInRoomId").val(id);
            $("#dogsInRoom").modal('show');
        }

        function moveMultipleDogs(roomId) {
            // Set the source room ID
            $("#source_room_id").val(roomId);
            
            // Get all dogs in this room
            var roomElement = $("#parent_" + roomId);
            var dogsContainer = $("#dogsCheckboxContainer");
            dogsContainer.empty();
            
            // Find all dogs in this room
            var dogsInRoom = roomElement.find('.child');
            
            if (dogsInRoom.length === 0) {
                dogsContainer.html('<p class="text-muted">Keine Hunde in diesem Zimmer gefunden.</p>');
                $("#moveMultipleDogsBtn").prop('disabled', true);
            } else {
                dogsInRoom.each(function() {
                    var dogElement = $(this);
                    var resId = dogElement.data('res-id');
                    var dogId = dogElement.attr('id').replace('child_', '');
                    var dogName = dogElement.find('.name').text();
                    var customerName = dogElement.find('a').text();
                    
                    // Create checkbox for each dog
                    var checkboxHtml = `
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="reservation_ids[]" value="${resId}" id="dog_${dogId}">
                            <label class="form-check-label" for="dog_${dogId}">
                                <strong>${dogName}</strong> - ${customerName}
                            </label>
                        </div>
                    `;
                    dogsContainer.append(checkboxHtml);
                });
                $("#moveMultipleDogsBtn").prop('disabled', false);
            }
            
            // Reset the target room selection
            $("#target_room_id").val('').trigger('change');
            
            // Initialize count
            $('#selectedCount').text('0 ausgewählt');
            
            // Show the modal
            $("#moveMultipleDogsModal").modal('show');
        }

        function selectAllDogs() {
            $('input[name="reservation_ids[]"]').prop('checked', true);
            var selectedCount = $('input[name="reservation_ids[]"]:checked').length;
            $('#selectedCount').text(selectedCount + ' ausgewählt');
            $('#moveMultipleDogsBtn').prop('disabled', false);
        }

        function deselectAllDogs() {
            $('input[name="reservation_ids[]"]').prop('checked', false);
            $('#selectedCount').text('0 ausgewählt');
            $('#moveMultipleDogsBtn').prop('disabled', true);
        }

        function loadCustomerDogs(selectedDogId) {
            if (!selectedDogId) {
                $('#customerDogsSection').hide();
                return;
            }

            // Get the customer ID from the selected option
            var selectedOption = $('#initial_dog_id option:selected');
            var customerId = selectedOption.data('customer-id');
            
            if (!customerId) {
                $('#customerDogsSection').hide();
                return;
            }

            // Get all dogs for this customer
            var customerDogs = [];
            $('#initial_dog_id option').each(function() {
                var option = $(this);
                if (option.data('customer-id') == customerId) {
                    customerDogs.push({
                        id: option.val(),
                        name: option.text().split(' - ')[0], // Extract dog name
                        customer: option.text().split(' - ')[1] // Extract customer info
                    });
                }
            });

            // Show the section if there are multiple dogs
            if (customerDogs.length > 1) {
                var container = $('#customerDogsContainer');
                container.empty();
                
                customerDogs.forEach(function(dog) {
                    var isSelected = dog.id == selectedDogId;
                    var checkboxHtml = `
                        <div class="form-check mb-2">
                            <input class="form-check-input customer-dog-checkbox" type="checkbox" 
                                   name="dog_ids[]" value="${dog.id}" id="customer_dog_${dog.id}" 
                                   ${isSelected ? 'checked' : ''}>
                            <label class="form-check-label" for="customer_dog_${dog.id}">
                                <strong>${dog.name}</strong> - ${dog.customer}
                                ${isSelected ? ' <span class="badge bg-success">Ausgewählt</span>' : ''}
                            </label>
                        </div>
                    `;
                    container.append(checkboxHtml);
                });

                $('#customerDogsSection').show();
                updateCustomerDogsCount();
            } else {
                $('#customerDogsSection').hide();
            }
        }

        function selectAllCustomerDogs() {
            $('.customer-dog-checkbox').prop('checked', true);
            updateCustomerDogsCount();
        }

        function deselectAllCustomerDogs() {
            $('.customer-dog-checkbox').prop('checked', false);
            updateCustomerDogsCount();
        }

        function updateCustomerDogsCount() {
            var selectedCount = $('.customer-dog-checkbox:checked').length;
            $('#customerDogsCount').text(selectedCount + ' ausgewählt');
        }

        function isJsonArray(str) {
            try {
                const parsed = JSON.parse(str);
                return Array.isArray(parsed);
            } catch (e) {
                return false;
            }
        }

        function dogInfo(id) {
            // get Dog details
            $.ajax({
                url: `/admin/reservation/${id}/fetch/all`,
                type: 'GET',
                success: function (res) {
                    if (res) {
                        // Store current dog data for document access
                        currentDogData = res;
                        var dog = res.dog;
                        var plan = res.plan;
                        $("#dogInfo #dogPicture").attr("src", "uploads/users/dogs/" + dog.picture);
                        $("#dogInfo #dog_name").html(dog.name);
                        $("#dogInfo #dog_breed").html(dog.compatible_breed);
                        //medications
                        $("#dogInfo #medication_morning").html((dog.morgen) ? dog.morgen : '-');
                        $("#dogInfo #medication_midday").html((dog.mittag) ? dog.mittag : '-');
                        $("#dogInfo #medication_evening").html((dog.abend) ? dog.abend : '-');
                        //eating habits
                        $("#dogInfo #eating_morning").html((dog.eating_morning) ? dog.eating_morning : '-');
                        $("#dogInfo #eating_midday").html((dog.eating_midday) ? dog.eating_midday : '-');
                        $("#dogInfo #eating_evening").html((dog.eating_evening) ? dog.eating_evening : '-');

                        //special food
                        $("#dogInfo #special_morning").html((dog.special_morning) ? dog.special_morning : '-');
                        $("#dogInfo #special_midday").html((dog.special_midday) ? dog.special_midday : '-');
                        $("#dogInfo #special_evening").html((dog.special_evening) ? dog.special_evening : '-');

                        if (dog.special_morning || dog.special_midday || dog.special_evening) {
                            $("#bf_span").addClass("text-success");
                        } else {
                            $("#bf_span").removeClass("text-success");
                        }

                        let parts = [];
                        if (dog.morgen) parts.push(dog.morgen);
                        if (dog.mittag) parts.push(dog.mittag);
                        if (dog.abend) parts.push(dog.abend);
                        if (dog.eating_habits && dog.eating_habits.includes("BF")) {
                            parts.push("BF");
                        }
                        $("#dogInfo #eating_habits").html(parts.join(" - "));

                        $("#dogInfo #health_problems").html(dog.health_problems);
                        $("#dogInfo #allergy").html((dog.allergy) ? dog.allergy : '');

                        // $("#dogInfo #dog_name").after('<span class="fs-3 fw-bold text-black ms-3">-</span>');
                        if (dog.allergy) {
                            $("#allergy").html(dog.allergy);  // Display allergy
                            $("#separator").show();  // Show separator
                        } else {
                            $("#allergy").html('');  // Clear allergy text
                            $("#separator").hide();  // Hide separator
                        }

                        //dog allergy

                        $("#dogInfo #dog_id").html(dog.id);
                        $("#dogInfo #plan_title").html((plan?.title) ? plan?.title : '');
                        $("#dogInfo #plan_type").html((plan?.type) ? plan?.type : '');
                        $("#dogInfo #plan_price").html((plan?.price) ? plan?.price + "&euro;" : '');
                        $("#dogInfo #plan_discount").html((plan?.discount) ? plan?.discount + "&euro;" : '');
                        $("#dogInfo #dog_id").html(dog.id);
                        $("#dogInfo #gender").html(dog.gender);
                        $("#dogInfo #name").html(dog.name);
                        $("#dogInfo #customer_name").html(`<a href='/admin/customers/${dog.customer.id}/preview' target='_blank'>${dog.customer.name}</a>`);

                        // calculate age
                        // var age = dog.age;
                        var dateObj = new Date(dog.age);
                        var day = dateObj.getUTCDate();
                        var month = dateObj.getUTCMonth() + 1;
                        var year = dateObj.getUTCFullYear();
                        day = day < 10 ? "0" + day : day;
                        month = month < 10 ? "0" + month : month;
                        var age = day + "." + month + "." + year;
                        // if(age_date)
                        // {
                        //   var birthdate = new Date(age_date);
                        //   var currentDate = new Date();
                        //   var timeDifference = currentDate.getTime() - birthdate.getTime();
                        //   var age = Math.floor(timeDifference / (1000 * 60 * 60 * 24));
                        // }
                        // else{
                        //   var age = '';
                        // }

                        // $("#dogInfo #age").html(age);
                        $("#dogInfo #age").html(`${dog.age} (${dog.age_in_years})`);

                        if (dog.neutered == 1) {
                            $("#dogInfo #kastrated").html('Ja');
                        } else {
                            $("#dogInfo #kastrated").html('Nein');
                        }
                        $("#dogInfo #breed").html(dog.compatible_breed);
                        $("#dogInfo #weight").html(dog.weight);
                        $("#dogInfo #chipnumber").html(dog.chip_number);
                        if (dog.is_medication == 1) {
                            $("#dogInfo #medicated").html(dog.medication);
                        }
                        $("#dogInfo #is_problem").html(dog.health_problems);
                        if (isJsonArray(dog.eating_habits)) {
                            var habits = JSON.parse(dog.eating_habits);

                            var morning = habits.find(habit => habit == 'Morgen') ? 1 : 0;
                            var afternoon = habits.find(habit => habit == 'Mittag') ? 1 : 0;
                            var evening = habits.find(habit => habit == 'Abend') ? 1 : 0;
                            var bf = habits.find(habit => habit == 'BF') ? "BF" : "";
                            var eating_habits = bf + morning + '-' + afternoon + '-' + evening;

                            if (bf != '') {
                                $("#dogInfo #bfinfo").addClass('bfFormat');
                            } else {
                                $("#dogInfo #bfinfo").removeClass('bfFormat');
                            }
                            habits = habits.join(' - ');

                        } else {
                            $("#dogInfo #bfinfo").removeClass('bfFormat');
                            var habits = dog.eating_habits;
                            var eating_habits = '0-0-0';
                        }

                        $("#dogInfo #routine").html(habits);

                        $("#dogInfo #bfinfo").html(eating_habits);


                        // Show Dog Friends
                        if (dog.friends.length > 0) {
                            var html = "";
                            dog.friends.forEach((item, index) => {
                                if (item.dog) {
                                    html += '<tr>';
                                    html += `<td><img src="uploads/users/dogs/${item.dog.picture}" width="40" height="40" class="rounded img-fluid" alt="Friend Picture" /></td>`;
                                    html += `<td>${item.dog.name}</td>`;
                                    // parse date
                                    var dateObj = new Date(item.created_at);
                                    var day = dateObj.getUTCDate();
                                    var month = dateObj.getUTCMonth() + 1;
                                    var year = dateObj.getUTCFullYear();
                                    day = day < 10 ? "0" + day : day;
                                    month = month < 10 ? "0" + month : month;
                                    var formattedDate = day + "." + month + "." + year;
                                    html += `<td>${formattedDate}</td>`;
                                    html += `<td><button class="no-style text-danger" style="font-size:19px" onclick="removeFriend('${item.id}','${id}')"><i class="far fa-times-circle"></i> Remove</button></td>`;
                                    html += '</tr>';
                                }
                            });
                            $("#hundeFriends tbody").html(html);
                        } else {
                            var html = "<tr><td colspan='4' class='text-center'>Keine Datensätze gefunden</td></tr>";
                            $("#hundeFriends tbody").html(html);
                        }

                        // Show Visit History
                        if (dog.visit_history && dog.visit_history.visits && dog.visit_history.visits.length > 0) {
                            var visitHtml = "";
                            dog.visit_history.visits.forEach((visit, index) => {
                                visitHtml += '<tr>';
                                visitHtml += `<td>${visit.checkin_date} - ${visit.checkout_date}</td>`;
                                visitHtml += `<td>${visit.duration} Tage</td>`;
                                visitHtml += `<td>€${visit.daily_price}/Tag = €${visit.actual_amount.toFixed(2)}</td>`;
                                visitHtml += '</tr>';
                            });
                            $("#visitHistory tbody").html(visitHtml);
                            $("#totalDays").text(dog.visit_history.total_days);
                            $("#totalAmount").text('€' + dog.visit_history.total_amount.toFixed(2));
                        } else {
                            var visitHtml = "<tr><td colspan='3' class='text-center'>Keine Besuche gefunden</td></tr>";
                            $("#visitHistory tbody").html(visitHtml);
                            $("#totalDays").text('0');
                            $("#totalAmount").text('€0.00');
                        }

                        // Checkin/out dates
                        $("#dogInfo #rangepicker").val(res.res_date);
                        $("#dogInfo #note").val(res.dog.note);
                        $("#dogInfo #note_id").val(res.dog.id);
                        $("#dogInfo #res_id").val(res.id);
                        $("#vaccination_dog_id").val(dog.id);

                        fetchVaccinations(dog.id);
                        checkVaccinationAlerts(dog.id);
                        
                        // Load and display documents (use already loaded data)
                        if (dog.documents) {
                            displayDogDocuments(dog.documents);
                        } else {
                            displayDogDocuments([]);
                        }
                        
                        // Refresh notifications after opening dog info
                        setTimeout(() => {
                            loadNotifications();
                        }, 500);

                        $("#dogInfo").modal('show');
                    }
                }
            })

        }

        function removeFriend(id, res_id) {
            $.ajax({
                url: "{{route('admin.dog.remove.friend')}}",
                type: 'POST',
                data: {_token: '{{csrf_token()}}', id: id},
                success: function (res) {
                    if (res) {
                        dogInfo(res_id);
                    }
                }
            })
        }

        function moveDog(id, res_id) {
            $("#moveDog #resIdMoveDog").val(res_id);
            $("#moveDog #dogIdMoveDog").val(id);
            $("#moveDog").modal('show');
        }

        function moveFromRes(id, res_id) {
            var idBeingDragged = 'child_' + id;
            if ($('.scrumboard').find('#' + idBeingDragged).length > 0) {
                alert('Dieser Hund ist bereits eingecheckt und kann nicht ein weiteres mal einchecken')
                return;
            }

            $("#moveDog #dogIdMoveDog").val(id);
            $("#moveDog #resIdMoveDog").val(res_id);
            $("#moveDog").modal('show');

        }

        function checkoutModal(id, checkin, plan_id, plan_price) {
            // get reservation data

            $.ajax({
                url: "{{route('admin.reservation.balance')}}",
                type: 'POST',
                data: {_token: "{{csrf_token()}}", id: id},
                success: function (res) {
                    if (res) {
                        var saldoRaw = parseFloat(res.total);
                        if (isNaN(saldoRaw)) {
                            saldoRaw = 0;
                        }

                        var doc = res.doc;

                        var checkin_date = new Date(checkin);
                        var checkout_date = new Date(res.checkout_date);

                        var today = new Date();
                        // Calculate days based on configuration (inclusive or exclusive)
                        // Same-day (29-29) always counts as 1 day regardless of mode
                        // Inclusive: both checkin date and today count (29-30 = 2 days, 29-31 = 3 days)
                        // Exclusive: days between count, same-day is 1 (29-30 = 1 day, 29-31 = 2 days)
                        var time_difference = today.getTime() - checkin_date.getTime();
                        var difference_in_days = Math.floor(time_difference / (1000 * 3600 * 24));
                        var daysCalculationMode = @json($daysCalculationMode ?? 'inclusive');
                        
                        // Same-day checkin/checkout always counts as 1 day
                        if (difference_in_days === 0) {
                            var days = 1;
                        } else {
                            var days = (daysCalculationMode === 'inclusive') ? difference_in_days + 1 : difference_in_days;
                        }

                        var formatted_checkin_date = checkin_date.toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });

                        var formatted_checkout_date = today.toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });

                        formatted_checkin_date = formatted_checkin_date.split('/').join(".");
                        formatted_checkout_date = formatted_checkout_date.split('/').join(".");

                        // Store existing customer balance for cumulative calculation
                        // saldoRaw from backend: positive = customer has credit, negative = customer owes
                        var existingBalance = saldoRaw || 0;
                        $('#checkoutModal').data('existingBalance', existingBalance);
                        
                        // Store VAT settings from backend (prices are always VAT inclusive)
                        // Handle 0% VAT correctly - use nullish coalescing to only default when null/undefined
                        var vatPercentage = (res.vat_percentage !== null && res.vat_percentage !== undefined) 
                            ? parseFloat(res.vat_percentage) 
                            : 20;
                        // If parseFloat returns NaN, default to 20
                        if (isNaN(vatPercentage)) {
                            vatPercentage = 20;
                        }
                        
                        $('#checkoutModal').data('vatPercentage', vatPercentage);
                        
                        // Initialize saldo display with existing balance
                        updateSaldoDisplay(existingBalance);
                        
                        // Show/hide wallet checkbox based on balance
                        if (existingBalance > 0) {
                            // Customer has credit, show wallet checkbox
                            $('#walletCheckboxContainer').show();
                            $('#wallet_available').text('+' + existingBalance.toFixed(2) + '€');
                            $('#use_wallet').prop('checked', false);
                        } else {
                            // Customer owes or has zero balance, hide wallet checkbox
                            $('#walletCheckboxContainer').hide();
                            $('#use_wallet').prop('checked', false);
                        }
                        
                        // Hide wallet breakdown initially
                        $('#walletBreakdown').hide();
                        
                        // Check gateway selection and hide only HelloCash section if Banküberweisung is selected
                        var selectedGateway = $('#checkoutModal #gateway').val();
                        if (selectedGateway === 'Bank') {
                            $('#hellocashSection').hide();
                            $('#send_to_hellocash').prop('checked', false);
                        }

                        $('#checkoutModal #checkoutId').val(id);
                        $('#checkoutModal #days').val(days);
                        $('#checkoutModal #checkin').html(formatted_checkin_date);
                        $('#checkoutModal #checkout').html(formatted_checkout_date + ` (${days} Tage)`);
                        $('#checkoutModal #checkout_date').val(formatted_checkout_date);

                        var planBasePrice = parseFloat(plan_price);
                        if (isNaN(planBasePrice)) {
                            planBasePrice = 0;
                        }

                        var selectedPlanId = plan_id;
                        if (days > 1 && doc.dog && doc.dog.reg_plan_obj) {
                            selectedPlanId = doc.dog.reg_plan_obj.id;
                        } else if (doc.dog && doc.dog.day_plan_obj) {
                            selectedPlanId = doc.dog.day_plan_obj.id;
                        }

                        manualTotalOverride = false;
                        if (selectedPlanId) {
                            $('#checkoutModal #price_plan').val(selectedPlanId).trigger('change');
                        }

                        $('#checkoutModal #special_cost').val('0.00');
                        $('#checkoutModal #total_amount').val('0.00');
                        $('#checkoutModal #received_amount').val('0.00');
                        $('#checkoutModal #remaining_amount').val('0.00');

                        // Fallback if the triggered change could not resolve the plan price
                        if (!$('#checkoutModal #plan_cost').val()) {
                            var fallbackCost = 0;
                            var selectedPlanId = $('#checkoutModal #price_plan').val();
                            var plan = pricePlans.find(function (item) {
                                return item.id == selectedPlanId;
                            });
                            var isFlatRate = plan && plan.flat_rate == 1;
                            
                            if (isFlatRate) {
                                // Flat rate: use plan price directly
                                fallbackCost = planBasePrice;
                            } else if (days > 1) {
                                fallbackCost = days * planBasePrice;
                            } else if (doc.dog && doc.dog.day_plan_obj) {
                                fallbackCost = doc.dog.day_plan_obj.price;
                            } else {
                                fallbackCost = planBasePrice;
                            }
                            $('#checkoutModal #plan_cost').val(parseFloat(fallbackCost).toFixed(2));
                        }
                        
                        // Check if initial plan is flat rate and hide discount section
                        var initialPlanId = $('#checkoutModal #price_plan').val();
                        if (initialPlanId) {
                            var initialPlan = pricePlans.find(function (item) {
                                return item.id == initialPlanId;
                            });
                            if (initialPlan && initialPlan.flat_rate == 1) {
                                $('#discountSection').hide();
                                $('#discount1').prop('checked', true);
                            }
                        }

                        recalcInvoiceTotals({updateReceived: true, forceAutoTotal: true});
                        
                        // After recalculating, update saldo with cumulative balance
                        // This ensures the existing balance is shown even when amounts are reset
                        setTimeout(function() {
                            updateRemaining();
                        }, 100);

                        $('#checkoutModal').modal('show');
                     }
                 }
             });

        }

        function submitCheckoutForm() {
            var form = document.querySelector('#checkoutModal form');
            if (!form) return;

            var submitBtn = document.getElementById('checkoutSubmitBtn');
            if (!submitBtn) return;

            // Prevent multiple submissions
            if (submitBtn.disabled) {
                return false;
            }

            // Check if HelloCash is selected
            var sendToHelloCash = $('#send_to_hellocash').is(':checked');
            
            if (sendToHelloCash) {
                // Show confirmation modal with invoice amount
                var invoiceTotal = parseFloat($('#total_amount').val()) || 0;
                $('#confirmInvoiceAmount').text(invoiceTotal.toFixed(2) + '€');
                
                // Store form data for later submission
                $('#checkoutModal').data('pendingFormData', new FormData(form));
                
                $('#hellocashConfirmModal').modal('show');
                return false;
            } else {
                // Proceed with normal checkout
                processCheckout(form, submitBtn);
            }

            return false;
        }

        // Handle HelloCash confirmation
        $('#confirmHelloCashBtn').on('click', function() {
            var form = document.querySelector('#checkoutModal form');
            var submitBtn = document.getElementById('checkoutSubmitBtn');
            var formData = $('#checkoutModal').data('pendingFormData');
            
            if (!formData) {
                formData = new FormData(form);
            }
            
            // Close confirmation modal
            $('#hellocashConfirmModal').modal('hide');
            
            // Process checkout with HelloCash
            processCheckoutWithHelloCash(formData, submitBtn);
        });

        function processCheckout(form, submitBtn) {
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
            var originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Bitte warten...';

            // Collect form data
            var formData = new FormData(form);
            
            // Ensure use_wallet is included (0 if unchecked, 1 if checked)
            if (!$('#use_wallet').is(':checked')) {
                formData.set('use_wallet', '0');
                formData.set('wallet_amount', '0');
            } else {
                // Calculate and send wallet amount for validation
                var existingBalance = parseFloat($('#checkoutModal').data('existingBalance')) || 0;
                var total = parseFloat($("#checkoutModal #total_amount").val()) || 0;
                var walletAmount = Math.min(existingBalance, total);
                formData.set('wallet_amount', walletAmount.toFixed(2));
            }

            // Submit via AJAX
            $.ajax({
                url: "{{route('admin.reservation.checkout')}}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Handle Bank invoice PDF if present
                    if (response.invoice && response.invoice.success && response.invoice.invoice_pdf_base64) {
                        try {
                            var binaryString = atob(response.invoice.invoice_pdf_base64);
                            var bytes = new Uint8Array(binaryString.length);
                            for (var i = 0; i < binaryString.length; i++) {
                                bytes[i] = binaryString.charCodeAt(i);
                            }
                            
                            var blob = new Blob([bytes], { type: 'application/pdf' });
                            var pdfUrl = URL.createObjectURL(blob);
                            window.open(pdfUrl, '_blank');
                            
                            setTimeout(function() {
                                URL.revokeObjectURL(pdfUrl);
                            }, 1000);
                        } catch (e) {
                            console.error('Error opening PDF:', e);
                            alert('Fehler beim Öffnen der Rechnung: ' + e.message);
                        }
                    }
                    
                    // Close modal
                    $('#checkoutModal').modal('hide');
                    // Reload page on success
                    window.location.reload();
                },
                error: function(xhr, status, error) {
                    // Re-enable button on error
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('disabled');
                    submitBtn.innerHTML = originalText;

                    // Show error message
                    var errorMessage = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                    alert(errorMessage);
                }
            });
        }

        function processCheckoutWithHelloCash(formData, submitBtn) {
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
            var originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Sende an Registrierkasse...';

            // Ensure use_wallet is included
            if (!$('#use_wallet').is(':checked')) {
                formData.set('use_wallet', '0');
                formData.set('wallet_amount', '0');
            } else {
                // Calculate and send wallet amount for validation
                var existingBalance = parseFloat($('#checkoutModal').data('existingBalance')) || 0;
                var total = parseFloat($("#checkoutModal #total_amount").val()) || 0;
                var walletAmount = Math.min(existingBalance, total);
                formData.set('wallet_amount', walletAmount.toFixed(2));
            }
            formData.set('send_to_hellocash', '1');

            // Submit via AJAX
            $.ajax({
                url: "{{route('admin.reservation.checkout')}}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.hellocash && response.hellocash.success && response.hellocash.invoice_pdf_base64) {
                        try {
                            var binaryString = atob(response.hellocash.invoice_pdf_base64);
                            var bytes = new Uint8Array(binaryString.length);
                            for (var i = 0; i < binaryString.length; i++) {
                                bytes[i] = binaryString.charCodeAt(i);
                            }
                            
                            var blob = new Blob([bytes], { type: 'application/pdf' });
                            var pdfUrl = URL.createObjectURL(blob);
                            window.open(pdfUrl, '_blank');
                            
                            setTimeout(function() {
                                URL.revokeObjectURL(pdfUrl);
                            }, 1000);
                        } catch (e) {
                            console.error('Error opening PDF:', e);
                        }
                        
                        $('#checkoutModal').modal('hide');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        $('#checkoutModal').modal('hide');
                        window.location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    // Re-enable button on error
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('disabled');
                    submitBtn.innerHTML = originalText;

                    // Show error message
                    var errorMessage = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                    alert(errorMessage);
                }
            });
        }

        function fetchTodos() {
            var date = $("#date_heading").html();
            var parts = date.split('.');
            date = parts[1] + '/' + parts[0] + '/' + parts[2];

            var incomplete = '';
            var complete = '';

            $.ajax({
                url: "{{route('admin.todo.fetch')}}",
                method: 'POST',
                data: {_token: "{{csrf_token()}}", date: date},
                success: function (response) {
                    if (response.length > 0) {
                        response.forEach((item, index) => {
                            if (item.status == 0) {
                                incomplete += '<div class="task_item">';
                                incomplete += `<button class="no-style text" id="toggleButton" onclick="invertTodoStatus('{{route('admin.toggle-todo-status')}}', 'POST', '{{csrf_token()}}', '${item.id}')">`
                                incomplete += `<p>${item.task}</p>`;
                                incomplete += '</button>';
                                incomplete += `<button class="no-style text-danger" style="margin-top:-15px;" onclick="deleteTodo('${item.id}')">`
                                incomplete += '<i class="mdi mdi-close"></i>';
                                incomplete += '</button>';
                                incomplete += '</div>';
                            } else {
                                complete += '<div class="task_item completed">';
                                complete += `<button class="no-style text" id="toggleButton" onclick="invertTodoStatus('{{route('admin.toggle-todo-status')}}', 'POST', '{{csrf_token()}}', '${item.id}')">`
                                complete += `<p>${item.task}</p>`;
                                complete += '</button>';
                                complete += `<button class="no-style text-danger" style="margin-top:-15px;" onclick="deleteTodo('${item.id}')">`
                                complete += '<i class="mdi mdi-close"></i>';
                                complete += '</button>';
                                complete += '</div>';
                            }
                        });
                    }
                    // Push HTML
                    $("#incompleteTasks").html(incomplete);
                    $("#completedTasks").html(complete);
                },
                error: function (xhr, status, error) {
                    // Handle errors
                    console.error(error);
                }
            });
        }

        var manualTotalOverride = false;

        function updateManualResetButton() {
            if (manualTotalOverride) {
                $("#checkoutModal #resetPlanPriceBtn").show();
            } else {
                $("#checkoutModal #resetPlanPriceBtn").hide();
            }
        }

        function recalcInvoiceTotals(options) {
            options = options || {};
            var planCost = parseFloat($("#checkoutModal #plan_cost").val()) || 0;
            var specialCost = parseFloat($("#checkoutModal #special_cost").val()) || 0;
            var discount = parseInt($("#checkoutModal input[name='discount']:checked").val()) || 0;

            // Calculate net total (prices are VAT exclusive)
            var netTotal = planCost + specialCost;
            if (discount > 0) {
                netTotal = netTotal * (1 - (discount / 100));
            }
            netTotal = parseFloat(netTotal.toFixed(2));

            if (manualTotalOverride && !options.forceAutoTotal) {
                var manualGrossTotal = parseFloat($("#checkoutModal #total_amount").val()) || 0;
                var adjustedPlanCost = Math.max(0, manualGrossTotal - specialCost);
                $("#checkoutModal #plan_cost").val(adjustedPlanCost.toFixed(2));
                calculateVATBreakdownFromGross(manualGrossTotal);
            } else {
                // Automatically calculate VAT and gross total
                calculateVATBreakdown(netTotal);
                var grossTotal = parseFloat($('#vat_gross_amount').text().replace('€', '').trim());
                $("#checkoutModal #total_amount").val(grossTotal.toFixed(2));
            }
            
            var displayTotal = parseFloat($("#checkoutModal #total_amount").val()) || 0;
            
            // Cap received amount at total
            $("#checkoutModal #received_amount").attr({
                min: 0,
                max: displayTotal
            });

            if (options.updateReceived) {
                $("#checkoutModal #received_amount").val(displayTotal.toFixed(2));
            }

            updateManualResetButton();
            updateRemaining();
        }
        
        function calculateVATBreakdown(netAmount) {
            // Get VAT settings from data attributes
            // Handle 0% VAT correctly - use nullish coalescing to only default when null/undefined
            var vatPercentageData = $('#checkoutModal').data('vatPercentage');
            var vatPercentage = (vatPercentageData !== null && vatPercentageData !== undefined) 
                ? parseFloat(vatPercentageData) 
                : 20;
            // If parseFloat returns NaN, default to 20
            if (isNaN(vatPercentage)) {
                vatPercentage = 20;
            }
            
            // Prices are VAT exclusive (net), calculate VAT and gross
            var vatAmount = netAmount * (vatPercentage / 100);
            var grossAmount = netAmount + vatAmount;
            
            // Round to 2 decimal places
            netAmount = parseFloat(netAmount.toFixed(2));
            vatAmount = parseFloat(vatAmount.toFixed(2));
            grossAmount = parseFloat(grossAmount.toFixed(2));
            
            // Update display
            $('#vat_percentage_display').text(vatPercentage);
            $('#vat_net_amount').text(netAmount.toFixed(2) + '€');
            $('#vat_amount').text(vatAmount.toFixed(2) + '€');
            $('#vat_gross_amount').text(grossAmount.toFixed(2) + '€');
            
        }

        function calculateVATBreakdownFromGross(grossAmount) {
            var vatPercentageData = $('#checkoutModal').data('vatPercentage');
            var vatPercentage = (vatPercentageData !== null && vatPercentageData !== undefined)
                ? parseFloat(vatPercentageData)
                : 20;
            if (isNaN(vatPercentage)) {
                vatPercentage = 20;
            }

            var netAmount = grossAmount / (1 + (vatPercentage / 100));
            var vatAmount = grossAmount - netAmount;

            netAmount = parseFloat(netAmount.toFixed(2));
            vatAmount = parseFloat(vatAmount.toFixed(2));
            grossAmount = parseFloat(grossAmount.toFixed(2));

            $('#vat_percentage_display').text(vatPercentage);
            $('#vat_net_amount').text(netAmount.toFixed(2) + '€');
            $('#vat_amount').text(vatAmount.toFixed(2) + '€');
            $('#vat_gross_amount').text(grossAmount.toFixed(2) + '€');
        }
        
        // Handle HelloCash checkbox change
        $(document).on('change', '#send_to_hellocash', function() {
            var sendToHelloCash = $(this).is(':checked');
            var currentReceived = parseFloat($("#checkoutModal #received_amount").val()) || 0;
            var currentTotal = parseFloat($("#checkoutModal #total_amount").val()) || 0;
            
            // Recalculate totals
            recalcInvoiceTotals();
            
            // Get the new total after recalculation
            var newTotal = parseFloat($("#checkoutModal #total_amount").val()) || 0;
            
            // If received amount was equal to old total, update it to new total
            // This ensures the received amount stays in sync
            if (Math.abs(currentReceived - currentTotal) < 0.01) {
                $("#checkoutModal #received_amount").val(newTotal.toFixed(2));
            }
            
            updateRemaining();
        });
        
        // Handle gateway (payment method) change - hide only HelloCash section for Banküberweisung
        $(document).on('change', '#gateway', function() {
            var selectedGateway = $(this).val();
            
            if (selectedGateway === 'Bank') {
                // Hide only HelloCash section for Banküberweisung
                $('#hellocashSection').hide();
                // Uncheck HelloCash checkbox if it was checked
                $('#send_to_hellocash').prop('checked', false);
                // Recalculate totals
                recalcInvoiceTotals();
            } else {
                // Show HelloCash section for Bar payment
                $('#hellocashSection').show();
                // Recalculate totals
                recalcInvoiceTotals();
            }
        });
        
        // Handle wallet checkbox change
        $(document).on('change', '#use_wallet', function() {
            var useWallet = $(this).is(':checked');
            var existingBalance = parseFloat($('#checkoutModal').data('existingBalance')) || 0;
            var total = parseFloat($("#checkoutModal #total_amount").val()) || 0;
            
            if (useWallet && existingBalance > 0) {
                // Wallet is being used
                var walletUsed = Math.min(existingBalance, total);
                var cashNeeded = Math.max(0, total - walletUsed);
                
                // Set received amount to cash needed
                $("#checkoutModal #received_amount").val(cashNeeded.toFixed(2));
            } else {
                // Wallet not used, set received to total
                $("#checkoutModal #received_amount").val(total.toFixed(2));
            }
            
            updateRemaining();
        });
        
        // Handle received amount input change
        $(document).on('input change', '#received_amount', function() {
            updateRemaining();
        });

        function updateRemaining() {
            var total = parseFloat($("#checkoutModal #total_amount").val()) || 0;
            var received = parseFloat($("#checkoutModal #received_amount").val()) || 0;
            var useWallet = $("#checkoutModal #use_wallet").is(':checked');
            var existingBalance = parseFloat($('#checkoutModal').data('existingBalance')) || 0;

            // Only prevent negative received amount
            if (received < 0) {
                received = 0;
                $("#checkoutModal #received_amount").val('0.00');
            }

            // Handle wallet usage
            var walletUsed = 0;
            var cashPayment = received; // Actual cash received from customer
            
            if (useWallet && existingBalance > 0) {
                // Use wallet balance (up to invoice total or available balance)
                walletUsed = Math.min(existingBalance, total);
                // cashPayment is the actual received amount (user input)
                // Don't override it - use the actual cash the customer pays
                
                // Show wallet breakdown
                $('#walletBreakdown').show();
                $('#wallet_used_display').text(walletUsed.toFixed(2) + '€');
                $('#cash_payment_display').text(cashPayment.toFixed(2) + '€');
            } else {
                // No wallet usage
                $('#walletBreakdown').hide();
                walletUsed = 0;
                cashPayment = received;
            }

            // Calculate current transaction remaining amount and advance payment
            // Use effective received (wallet + cash) for calculations
            var effectiveReceived = walletUsed + cashPayment;
            var currentRemaining, advancePayment;
            
            if (effectiveReceived > total) {
                // Customer paid more than invoice (advance payment)
                currentRemaining = 0;
                advancePayment = effectiveReceived - total;
            } else if (effectiveReceived < total) {
                // Customer paid less than invoice (still owes)
                currentRemaining = total - effectiveReceived;
                advancePayment = 0;
            } else {
                // Exact payment
                currentRemaining = 0;
                advancePayment = 0;
            }
            
            // Update remaining_amount field (for backward compatibility)
            $("#checkoutModal #remaining_amount").val(currentRemaining.toFixed(2));
            
            // Calculate cumulative balance: existing + (advance - remaining - wallet)
            // Positive balance = customer has credit (GREEN) - e.g., +50€
            // Negative balance = customer owes (RED) - e.g., -30€
            // Current transaction: advance (positive credit) - remaining (positive debt) - wallet (deducted) = net balance
            var currentNetBalance = advancePayment - currentRemaining - walletUsed;
            var cumulativeBalance = existingBalance + currentNetBalance;
            
            // Update saldo display with cumulative balance
            updateSaldoDisplay(cumulativeBalance);
            
            updateCheckoutStatus(total, effectiveReceived, currentRemaining);
        }
        
        function updateSaldoDisplay(balance) {
            var saldoElement = $("#checkoutModal #saldo");
            
            // Format balance with sign
            // Positive balance = customer has credit (GREEN) - e.g., +50€
            // Negative balance = customer owes (RED) - e.g., -30€
            var sign = balance >= 0 ? '+' : '';
            var formattedBalance = sign + balance.toFixed(2) + '€';
            saldoElement.text(formattedBalance);
            
            // Remove previous color classes
            saldoElement.removeClass('text-danger text-success');
            
            if (balance > 0) {
                // Customer has credit (positive balance) - GREEN
                saldoElement.addClass('text-success');
            } else if (balance < 0) {
                // Customer owes money (negative balance) - RED
                saldoElement.addClass('text-danger');
            } else {
                // Zero balance - no special color
                saldoElement.removeClass('text-danger text-success');
            }
        }

        function updateCheckoutStatus(total, received, remaining) {
            var epsilon = 0.01;
            var statusVal;

            // If invoice total is 0 (e.g., organization plan), automatically mark as paid
            if (total < epsilon) {
                statusVal = 1; // Bezahlt
            } else if (Math.abs(received) <= epsilon) {
                statusVal = 0; // Nicht bezahlt
            } else if (remaining > epsilon) {
                statusVal = 2; // Offen
            } else {
                statusVal = 1; // Bezahlt
            }

            $('#status_value').val(statusVal);
            $('#status_display').val(String(statusVal)).trigger('change.select2');
        }

        $(document).ready(function () {
            // Check vaccination alerts for all dogs on page load
            checkAllVaccinationAlerts();

            // Update Note
            $("#submitNote").on('click', function () {
                var note = $("#dogInfo #note").val();
                var dog_id = $("#dogInfo #note_id").val();
                $.ajax({
                    url: "{{route('admin.dog.note')}}",
                    type: "POST",
                    data: {_token: "{{csrf_token()}}", id: dog_id, note: note},
                    success: function (res) {
                        if (res) {
                            $("#note_saved").show();
                            setTimeout(() => {
                                $("#note_saved").hide();
                            }, 3000);
                        }
                    }
                })
            });

            // Update Reservation Date
            $("#resDateUpdate").on('click', function () {
                var date = $("#dogInfo #rangepicker").val();
                var id = $("#dogInfo #res_id").val();

                $.ajax({
                    url: "{{route('admin.res.date.update')}}",
                    type: 'POST',
                    data: {_token: "{{csrf_token()}}", id: id, date: date},
                    success: function (res) {
                        $("#reso_saved").show();
                        setTimeout(() => {
                            $("#reso_saved").hide();
                        }, 3000);
                    }
                })
            });


            // Check price change on checkout
            $("#checkoutModal #price_plan").on('change', function () {
                var days = parseInt($("#checkoutModal #days").val(), 10);
                if (!days || days < 1) {
                    days = 1;
                }
                var planId = parseInt(this.value, 10);
                var plan = pricePlans.find(function (item) {
                    return item.id == planId;
                });
                var price = plan ? parseFloat(plan.price) : 0;
                var isFlatRate = plan && plan.flat_rate == 1;
                
                // For flat rate plans, don't multiply by days
                var planCost = isFlatRate ? price : (price * days);
                $('#checkoutModal #plan_cost').val(planCost.toFixed(2));
                
                // Hide/disable discount section for flat rate plans
                if (isFlatRate) {
                    $('#discountSection').hide();
                    // Reset discount to 0
                    $('#discount1').prop('checked', true);
                } else {
                    $('#discountSection').show();
                }
                
                recalcInvoiceTotals({updateReceived: true, forceAutoTotal: true});
             });
 
             // Discount change on checkout
             $("#checkoutModal input[name='discount']").on('change', function () {
                 recalcInvoiceTotals({updateReceived: true, forceAutoTotal: true});
             });
 
             // Special cost change on checkout - recalculate on input and change events
             $("#checkoutModal #special_cost").on('input change', function () {
                 recalcInvoiceTotals({updateReceived: true, forceAutoTotal: true});
             });
 
             $("#checkoutModal #total_amount").on('input change', function () {
                 manualTotalOverride = true;
                 updateManualResetButton();
                 var currentGrossTotal = parseFloat($(this).val()) || 0;
                 var currentSpecialCost = parseFloat($("#checkoutModal #special_cost").val()) || 0;
                 var adjustedPlanCost = Math.max(0, currentGrossTotal - currentSpecialCost);
                 $("#checkoutModal #plan_cost").val(adjustedPlanCost.toFixed(2));
                 calculateVATBreakdownFromGross(currentGrossTotal);
                 updateRemaining();
             });

            $("#checkoutModal #resetPlanPriceBtn").on('click', function () {
                manualTotalOverride = false;
                recalcInvoiceTotals({forceAutoTotal: true});
            });

            $("#checkoutModal #received_amount").on('input change', function () {
                updateRemaining();
            });

        });

        $(".dogItems").sortable({
            connectWith: ".dogItems",
            handle: ".portlet-header",
            cancel: ".portlet-toggle",
            helper: "clone",
            appendTo: "body",
            zIndex: 10000,
            placeholder: "portlet-placeholder ui-corner-all",
            start: function (event, ui) {
                var idBeingDragged = ui.item.attr('id');
                clearDragIndicators();
                if (ui.item.parents().parents().parents().attr('id') === 'parent_res') {
                    if ($('.scrumboard').find('#' + idBeingDragged).length > 0) {
                        alert('Dieser Hund ist bereits eingecheckt und kann nicht ein weiteres mal einchecken');
                        $(this).sortable('cancel');
                        clearDragIndicators();
                        return;
                    }
                }
                // Ensure dragged item is on top
                ui.helper.css({
                    'z-index': 9999,
                    'position': 'absolute' // Ensure the element is positioned relative or absolute
                });

                // Temporarily set parent container overflow to visible
                ui.helper.parents().css('overflow', 'visible');
            },
            update: function (event, ui) {
                var getDogID = ui.item.attr("id");
                var dogID = getDogID.split("_")[1];
                if (typeof dogs[dogID] === 'undefined') {
                    if (ui.item.parent().hasClass('child')) {
                    } else {
                        ui.item.addClass('col-md-4 col-lg-4 col-xl-4 col-xxl-3 child');
                    }
                }
            },

            over: function (event, ui) {
                $(this).addClass('highlight');
            },

            out: function (event, ui) {
                $(this).removeClass('highlight');
            },
            // Ensure the dragged item is above other elements while dragging
            sort: function (event, ui) {
                ui.helper.css('z-index', 9999);
            },
            beforeStop: function (event, ui) {
                var reservationID = ui.item.attr("data-res-id");
                var position = ui.item.attr("data-position");
                var getDogID = ui.item.attr("id");
                var dogID = getDogID.split("_")[1];
                var dragType = position === 'left' ? 'checkin' : 'move';
                var $currentItem = ui.item;
                var sortableInstance = $(this);

                var tag = ui.placeholder.parent().parent().parent().attr("id");
                if (tag == 'parent_res') {
                    $(this).sortable('cancel');
                    return;
                }

                var getRoomID = ui.item.parents().parents().parents().attr('id');
                var getRoom = getRoomID.split("_")[1];

                if (getRoom == "res") {
                    $(this).sortable('cancel');
                    return;
                }

                if (dragType === 'checkin') {
                    var duplicateCard = $(".scrumboard #child_" + dogID).filter(function () {
                        return this !== $currentItem[0];
                    });

                    if (duplicateCard.length > 0) {
                        alert('Dieser Hund ist bereits eingecheckt und kann nicht zweimal eingecheckt werden.');
                        sortableInstance.sortable('cancel');
                        clearDragIndicators();
                        return;
                    }

                    var room = rooms.find((item, index) => {
                        return item.id == getRoom;
                    });

                    var available_dogs = $("#parent_" + getRoom + ' .dogItems .child');
                    var capacity = parseInt(room.capacity);
                    var reserved = available_dogs.length;

                    if (reserved >= capacity) {
                        alert('Der Raum ist bereits voll. Bitte wählen Sie ein anderes Zimmer.');
                        sortableInstance.sortable('cancel');
                        clearDragIndicators();
                    } else {
                        $.ajax({
                            url: "{{route('admin.reservation.room.update')}}",
                            type: 'POST',
                            data: {
                                _token: "{{csrf_token()}}",
                                id: reservationID,
                                room_id: getRoom,
                                status: 1,
                                drag_type: dragType
                            },
                            dataType: "json",
                            success: function (data) {
                                if (data && data.error) {
                                    alert(data.message || 'Dieser Hund ist bereits eingecheckt und kann nicht zweimal eingecheckt werden.');
                                    sortableInstance.sortable('cancel');
                                    updateRoomsCount();
                                    clearDragIndicators();
                                    return;
                                }

                                updateRoomsCount();
                                clearDragIndicators();

                                $currentItem.removeAttr('data-position');

                                if (data && data.showModal) {
                                    $("#friendshipModal #dog_id").val(dogID);
                                    $("#friendshipModal #room_id").val(getRoom);
                                    $("#friendshipModal").modal('show');
                                }
                            },
                            error: function (xhr) {
                                var message = 'Fehler beim Verschieben der Reservierung.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                alert(message);
                                sortableInstance.sortable('cancel');
                                updateRoomsCount();
                                clearDragIndicators();
                            }
                        });
                    }
                } else {
                    var room = rooms.find((item, index) => {
                        return item.id == getRoom;
                    });

                    var available_dogs = $("#parent_" + getRoom + ' .dogItems .child');
                    var capacity = parseInt(room.capacity);
                    var reserved = available_dogs.length - 1;

                    if (reserved >= capacity) {
                        alert('Der Zielraum ist bereits voll. Bitte wählen Sie ein anderes Zimmer.');
                        sortableInstance.sortable('cancel');
                        clearDragIndicators();
                    } else {
                        // Update reservation room_id
                        $.ajax({
                            url: "{{route('admin.reservation.room.update')}}",
                            type: 'POST',
                            data: {
                                _token: "{{csrf_token()}}",
                                id: reservationID,
                                room_id: getRoom,
                                dog_id: dogID,
                                drag_type: dragType
                            },
                            dataType: "json",
                            success: function (data) {
                                if (data && data.error) {
                                    alert(data.message || 'Dieser Hund ist bereits eingecheckt und kann nicht zweimal eingecheckt werden.');
                                    sortableInstance.sortable('cancel');
                                    updateRoomsCount();
                                    clearDragIndicators();
                                    return;
                                }
                                rooms.map((itemD) => {
                                    itemD.reservations.find((fnd) => {
                                        if (fnd.id == reservationID) {
                                            fnd.room_id = getRoom;
                                        }
                                    })
                                });

                                dogs[dogID].room_id = getRoom;
                                updateRoomsCount();
                                clearDragIndicators();
                                if (data.showModal == true) {
                                    $("#friendshipModal #dog_id").val(dogID);
                                    $("#friendshipModal #room_id").val(getRoom);
                                    $("#friendshipModal").modal('show');
                                }
                            },
                            error: function (xhr) {
                                var message = 'Fehler beim Verschieben der Reservierung.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                alert(message);
                                sortableInstance.sortable('cancel');
                                updateRoomsCount();
                                clearDragIndicators();
                            }
                        });
                    }
                }
            },

            stop: function (event, ui) {
                ui.helper.parents().css('overflow', '');
                ui.helper.css({
                    'z-index': '',
                    'position': ''
                });
            },

        }).disableSelection();

        function updateRoomsCount() {
            $(".rooms .room").map((i, room) => {
                var id = room.getAttribute('id');
                var length = $("#" + id + ' .dogItems .child').length;
                $(`#${id} .card-header .count`).html(length)
            });
        }

        function clearDragIndicators() {
            $('.dogItems').removeClass('highlight');
        }

        function searchDogPlan(id) {
            var dogs = @json("$dogs");
            dogs = JSON.parse(dogs);
            var dogi = false;

            dogs.map((dog) => {
                if (dog.id == id) {
                    dogi = dog;
                }
            });

            var plan = dogi.reg_plan;

            var options = $("#dogsInRoom #plan_id option");
            options.each(function () {
                if ($(this).val() == plan) {
                    $(this).prop('selected', true);
                }
            });

            $("#dogsInRoom #plan_id").trigger('change');
        }

        function openXimmerModal() {
            $("#planmodel2").removeClass('d-none');
        }

        function closePlanModal() {
            $("#planmodel2").addClass('d-none');
        }

        // Normalize currency inputs to positive values with two decimals
        $(".absInput").on('change blur', function () {
            var numericValue = parseFloat(this.value);

            if (isNaN(numericValue)) {
                numericValue = 0;
            }

            numericValue = Math.abs(numericValue);
            this.value = numericValue.toFixed(2);

            if (this.id === 'received_amount' || this.id === 'total_amount') {
                updateRemaining();
            }
        });
    </script>
    {{-- Script Area Starts Here --}}
    <script>
        $(function () {
            $("#selectableMoveDog").selectable({
                stop: function () {
                    var selectedId = '';
                    $(".ui-selected", this).each(function () {
                        selectedId = $(this).data('id'); // Get the data-id of the selected item
                    });
                    $("#selectedRoomMoveDog").val(selectedId); // Set the hidden input value
                }
            });

            // Handle move dog form submission via AJAX
            $('#moveDogForm').on('submit', function(e) {
                e.preventDefault();
                
                var roomId = $('#selectedRoomMoveDog').val();
                if (!roomId) {
                    alert('Bitte wählen Sie ein Zimmer aus!');
                    return false;
                }

                var formData = $(this).serialize();
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.error) {
                            alert(data.message || 'Fehler beim Verschieben des Hundes.');
                            return;
                        }

                        // Close the move modal
                        $('#moveDog').modal('hide');
                        
                        // Show friendship modal if needed (before reload)
                        if (data.showModal) {
                            $("#friendshipModal #dog_id").val(data.dog_id);
                            $("#friendshipModal #room_id").val(data.room_id);
                            
                            // Remove any existing handlers to prevent duplicates
                            $("#friendshipModal").off('hidden.bs.modal');
                            
                            // Show modal and reload after it's closed
                            $("#friendshipModal").modal('show');
                            
                            // Reload page when modal is hidden (user clicks Yes or No)
                            $("#friendshipModal").on('hidden.bs.modal', function() {
                                location.reload();
                            });
                        } else {
                            // No modal needed, reload immediately
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        var message = 'Fehler beim Verschieben des Hundes.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        alert(message);
                    }
                });
            });
        });

        $(function () {
            $("#selectablePlanModel2").selectable({
                stop: function () {
                    var selectedId = '';
                    $(".ui-selected", this).each(function () {
                        selectedId = $(this).data('id'); // Get the data-id of the selected item
                    });
                    $("#selectedRoomPlanModel2").val(selectedId); // Set the hidden input value
                }
            });
        });

        $(function () {
            $('[data-toggle="tooltip"]').tooltip();

            $(document).on('select2:open', () => {
                document.querySelector('.select2-search__field').focus();
            });

            // Handle multiple dogs move form submission
            $('#moveMultipleDogsForm').on('submit', function(e) {
                var selectedDogs = $('input[name="reservation_ids[]"]:checked').length;
                if (selectedDogs === 0) {
                    e.preventDefault();
                    alert('Bitte wählen Sie mindestens einen Hund aus!');
                    return false;
                }
                
                var targetRoom = $('#target_room_id').val();
                if (!targetRoom) {
                    e.preventDefault();
                    alert('Bitte wählen Sie ein Zielzimmer aus!');
                    return false;
                }
            });

            // Handle checkbox changes to enable/disable submit button
            $(document).on('change', 'input[name="reservation_ids[]"]', function() {
                var selectedCount = $('input[name="reservation_ids[]"]:checked').length;
                $('#selectedCount').text(selectedCount + ' ausgewählt');
                
                if (selectedCount > 0) {
                    $('#moveMultipleDogsBtn').prop('disabled', false);
                } else {
                    $('#moveMultipleDogsBtn').prop('disabled', true);
                }
            });

            // Handle customer dog checkbox changes
            $(document).on('change', '.customer-dog-checkbox', function() {
                updateCustomerDogsCount();
            });

            // Handle reservation form submission
            $('#addReservationForm').on('submit', function(e) {
                // Ensure the initially selected dog is always included
                var initialDogId = $('#initial_dog_id').val();
                
                if (!initialDogId) {
                    e.preventDefault();
                    alert('Bitte wählen Sie einen Hund aus!');
                    return false;
                }
                
                // Check if customer dogs section is visible (multiple dogs)
                if ($('#customerDogsSection').is(':visible')) {
                    // Multiple dogs case: check for selected checkboxes
                    var selectedDogs = $('input[name="dog_ids[]"]:checked').length;
                    if (selectedDogs === 0) {
                        e.preventDefault();
                        alert('Bitte wählen Sie mindestens einen Hund aus!');
                        return false;
                    }
                } else {
                    // Single dog case: add hidden input for the initial dog
                    // Remove any existing hidden inputs first to avoid duplicates
                    $('input[name="dog_ids[]"][type="hidden"]').remove();
                    
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'dog_ids[]',
                        value: initialDogId
                    }).appendTo(this);
                }
            });

        });

        fetchEventStatuses();

        // Vaccination AJAX Functions
        function fetchVaccinations(dogId) {
            $.ajax({
                url: `/admin/vaccinations/${dogId}`,
                type: 'GET',
                success: function(vaccinations) {
                    displayVaccinations(vaccinations);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching vaccinations:', error);
                    $("#vaccinationsTable tbody").html('<tr><td colspan="5" class="text-center">Fehler beim Laden der Impfungen</td></tr>');
                }
            });
        }

        function displayVaccinations(vaccinations) {
            if (vaccinations.length > 0) {
                var html = "";
                vaccinations.forEach(function(vaccination) {
                    var vaccinationDate = new Date(vaccination.vaccination_date);
                    var nextDate = new Date(vaccination.next_vaccination_date);
                    
                    var vaccinationFormatted = vaccinationDate.toLocaleDateString('de-DE');
                    var nextFormatted = nextDate.toLocaleDateString('de-DE');
                    
                    var statusCheckbox = '<div class="form-check d-flex align-items-center">' +
                        '<input class="form-check-input vaccination-status" type="checkbox" data-id="' + vaccination.id + '"' + 
                        (vaccination.is_vaccinated ? ' checked' : '') + '>' +
                        '<label class="form-check-label ms-2">Geimpft</label>' +
                        '</div>';
                    
                    html += '<tr data-id="' + vaccination.id + '">';
                    html += '<td>' + vaccination.vaccine_name + '</td>';
                    html += '<td>' + vaccinationFormatted + '</td>';
                    html += '<td>' + nextFormatted + '</td>';
                    html += '<td>' + statusCheckbox + '</td>';
                    html += '<td><button class="btn btn-sm btn-danger delete-vaccination" data-id="' + vaccination.id + '"><i class="fa fa-trash"></i></button></td>';
                    html += '</tr>';
                });
                $("#vaccinationsTable tbody").html(html);
            } else {
                $("#vaccinationsTable tbody").html('<tr><td colspan="5" class="text-center">Keine Impfungen gefunden</td></tr>');
            }
        }

        // Handle vaccination form submission
        $('#vaccinationForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                dog_id: $('#vaccination_dog_id').val(),
                vaccine_name: $('#vaccine_name').val(),
                vaccination_date: $('#vaccination_date').val(),
                next_vaccination_date: $('#next_vaccination_date').val()
            };

            const submitButton = $(this).find('button[type="submit"]');
            const originalText = submitButton.html();
            const originalClass = submitButton.attr('class');
            
            // Show loading state
            submitButton.html('<i class="fas fa-spinner fa-spin"></i> Speichern...').prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
            
            // Show loading indicator on table
            $("#vaccinationsTable tbody").html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Speichere Impfung...</td></tr>');

            $.ajax({
                url: '/admin/vaccinations',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        fetchVaccinations(formData.dog_id);
                        checkVaccinationAlerts(formData.dog_id);
                        $('#vaccinationForm')[0].reset();
                        $("#vaccination_saved").show();
                        setTimeout(() => {
                            $("#vaccination_saved").hide();
                        }, 3000);
                        
                        // Refresh notifications
                        setTimeout(() => {
                            loadNotifications();
                        }, 500);
                    } else {
                        alert('Fehler: ' + response.message);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Fehler beim Speichern der Impfung';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    submitButton.html(originalText).prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
                }
            });
        });

        // Handle vaccination deletion
        $(document).on('click', '.delete-vaccination', function() {
            const vaccinationId = $(this).data('id');
            const dogId = $('#vaccination_dog_id').val();
            const deleteButton = $(this);
            
            if (confirm('Möchten Sie diese Impfung wirklich löschen?')) {
                // Show loading state
                deleteButton.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                $("#vaccinationsTable tbody").html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Lösche Impfung...</td></tr>');
                
                $.ajax({
                    url: `/admin/vaccinations/${vaccinationId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            fetchVaccinations(dogId);
                            checkVaccinationAlerts(dogId);
                            $("#vaccination_deleted").show();
                            setTimeout(() => {
                                $("#vaccination_deleted").hide();
                            }, 3000);
                            
                            // Refresh notifications
                            setTimeout(() => {
                                loadNotifications();
                            }, 500);
                        } else {
                            alert('Fehler: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Fehler beim Löschen der Impfung';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        deleteButton.html('<i class="fa fa-trash"></i>').prop('disabled', false);
                    }
                });
            }
        });

        // ========== Dog Documents Functions ==========
        
        // Store current dog data globally for document access
        var currentDogData = null;

        // Load and display dog documents (refresh from server)
        function loadDogDocuments(dogId) {
            // Fetch fresh data from server
            $.ajax({
                url: `/admin/reservation/${dogId}/fetch/all`,
                type: 'GET',
                success: function(res) {
                    currentDogData = res;
                    if (res && res.dog && res.dog.documents) {
                        displayDogDocuments(res.dog.documents);
                    } else {
                        displayDogDocuments([]);
                    }
                },
                error: function() {
                    displayDogDocuments([]);
                }
            });
        }

        function displayDogDocuments(documents) {
            var tbody = $("#documentsTableBody");
            if (!documents || documents.length === 0) {
                tbody.html('<tr><td colspan="2" class="text-center">Keine Dokumente gefunden</td></tr>');
                return;
            }

            var html = "";
            documents.forEach(function(doc) {
                var documentUrl = '/uploads/users/documents/' + doc.file_path;
                
                html += '<tr data-id="' + doc.id + '">';
                html += '<td>';
                html += '<a href="' + documentUrl + '" target="_blank" class="text-decoration-none">' + doc.name + '</a>';
                html += '</td>';
                html += '<td class="text-end">';
                html += '<a href="' + documentUrl + '" download class="btn btn-sm btn-info me-1" title="Herunterladen"><i class="fa fa-download"></i></a>';
                html += '<button class="btn btn-sm btn-danger delete-document" data-id="' + doc.id + '" title="Löschen"><i class="fa fa-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            });
            tbody.html(html);
        }

        function formatFileSize(bytes) {
            if (!bytes) return 'Unknown';
            var units = ['B', 'KB', 'MB', 'GB'];
            var i = 0;
            while (bytes >= 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }
            return Math.round(bytes * 100) / 100 + ' ' + units[i];
        }

        // Handle add document button click
        $(document).on('click', '[data-bs-target="#addDocumentModal"]', function() {
            var dogId = $('#vaccination_dog_id').val();
            if (!dogId) {
                alert('Bitte öffnen Sie zuerst die Hundinformationen');
                return;
            }
            $('#documentDogId').val(dogId);
            $('#documentForm')[0].reset();
            $('#documentFileField').show();
            $('#documentFile').prop('required', true);
            $('#documentModalTitle').text('Dokument hinzufügen');
        });

        // Handle document form submission (create only)
        $('#documentForm').on('submit', function(e) {
            e.preventDefault();
            
            var dogId = $('#documentDogId').val();
            var formData = new FormData(this);
            
            $.ajax({
                url: `/admin/dogs/${dogId}/documents`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $('#addDocumentModal').modal('hide');
                        loadDogDocuments(dogId);
                        alert(response.message || 'Dokument erfolgreich gespeichert');
                    }
                },
                error: function(xhr) {
                    var message = 'Fehler beim Speichern des Dokuments';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert(message);
                }
            });
        });

        // Handle delete document
        $(document).on('click', '.delete-document', function() {
            if (!confirm('Sind Sie sicher, dass Sie dieses Dokument löschen möchten?')) {
                return;
            }
            
            var docId = $(this).data('id');
            var dogId = $('#vaccination_dog_id').val();
            var deleteButton = $(this);
            
            deleteButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: `/admin/dogs/documents/${docId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        loadDogDocuments(dogId);
                        alert(response.message || 'Dokument erfolgreich gelöscht');
                    }
                },
                error: function(xhr) {
                    var message = 'Fehler beim Löschen des Dokuments';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert(message);
                },
                complete: function() {
                    deleteButton.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                }
            });
        });

        // ========== End Dog Documents Functions ==========

        // Handle vaccination status checkbox change
        $(document).on('change', '.vaccination-status', function() {
            const vaccinationId = $(this).data('id');
            const isVaccinated = $(this).is(':checked');
            const dogId = $('#vaccination_dog_id').val();
            const checkbox = $(this);
            
            // Show loading state
            checkbox.prop('disabled', true);
            $("#vaccinationsTable tbody").html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Aktualisiere Status...</td></tr>');
            
            $.ajax({
                url: `/admin/vaccinations/${vaccinationId}/toggle`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    is_vaccinated: isVaccinated ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        const dogId = $('#vaccination_dog_id').val();
                        checkVaccinationAlerts(dogId);
                        fetchVaccinations(dogId);
                        $("#vaccination_updated").show();
                        setTimeout(() => {
                            $("#vaccination_updated").hide();
                        }, 3000);
                        
                        // Refresh notifications
                        setTimeout(() => {
                            loadNotifications();
                        }, 500);
                    } else {
                        alert('Fehler: ' + response.message);
                        // Revert checkbox state on error
                        checkbox.prop('checked', !isVaccinated);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Fehler beim Aktualisieren des Impfstatus';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    alert(errorMessage);
                    // Revert checkbox state on error
                    checkbox.prop('checked', !isVaccinated);
                },
                complete: function() {
                    // Reset checkbox state
                    checkbox.prop('disabled', false);
                }
            });
        });

        // Check vaccination alerts for all dogs on page load
        function checkAllVaccinationAlerts() {
            // Get all dog IDs from the page
            $('.child').each(function() {
                const dogId = $(this).attr('id').replace('child_', '');
                if (dogId) {
                    checkVaccinationAlerts(dogId);
                }
            });
        }

        // Check vaccination alerts for a specific dog
        function checkVaccinationAlerts(dogId) {
            $.ajax({
                url: `/admin/vaccinations/${dogId}`,
                method: 'GET',
                success: function(vaccinations) {
                    const today = new Date();
                    const upcomingVaccinations = vaccinations.filter(vaccination => {
                        const nextDate = new Date(vaccination.next_vaccination_date);
                        const diffTime = nextDate - today;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        return diffDays >= 0 && diffDays <= 3 && !vaccination.is_vaccinated;
                    });

                    const indicator = $(`#child_${dogId} .vaccination-indicator`);
                    if (upcomingVaccinations.length > 0) {
                        indicator.removeClass('d-none');
                    } else {
                        indicator.addClass('d-none');
                    }

                    // Display alerts for upcoming vaccinations in modal
                    if (upcomingVaccinations.length > 0) {
                        let alertHtml = '<div class="alert alert-danger mx-4 shadow-sm">';
                        alertHtml += '<h6 class="text-danger fw-bolder mb-2"><i class="mdi mdi-needle fs-5 fw-bolder text-danger"></i> Anstehende Impfungen</h6>';

                        upcomingVaccinations.forEach(vaccination => {
                            const nextDate = new Date(vaccination.next_vaccination_date);
                            const diffTime = nextDate - today;
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            
                            alertHtml += `<div class="ms-3 mb-1">`;
                            alertHtml += `• <strong>${vaccination.vaccine_name}</strong> - Fällig in ${diffDays} Tag${diffDays !== 1 ? 'en' : ''} (${nextDate.toLocaleDateString('de-DE')})`;
                            alertHtml += `</div>`;
                        });

                        alertHtml += '</div>';
                        
                        // Remove existing vaccination alerts
                        $('#dogInfo .alert-danger').remove();
                        
                        // Add new alert at the top of modal body
                        $('#dogInfo .modal-body').prepend(alertHtml);
                    } else {
                        // Remove vaccination alerts if none
                        $('#dogInfo .alert-danger').remove();
                    }
                }
            });
        }

        // ==================== TIMER FUNCTIONALITY ====================
        let timerInterval = null;
        let currentTimerId = null;
        let remainingSeconds = 0;

        // Toggle dropdown visibility
        function toggleTimerDropdown(event) {
            event.preventDefault();
            const dropdown = document.getElementById('timerDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('timerDropdown');
            const timerButton = document.getElementById('timerButton');
            
            if (dropdown && timerButton && 
                !dropdown.contains(event.target) && 
                !timerButton.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Select timer duration and start
        function selectTimerDuration(minutes) {
            // Hide dropdown
            document.getElementById('timerDropdown').style.display = 'none';
            
            // Start timer via AJAX
            fetch('{{ route('admin.timer.start') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    duration: minutes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentTimerId = data.timer.id;
                    remainingSeconds = data.timer.remaining;
                    startCountdown();
                } else {
                    alert(data.message || 'Fehler beim Starten des Timers');
                }
            })
            .catch(error => {
                console.error('Error starting timer:', error);
                alert('Fehler beim Starten des Timers. Bitte versuchen Sie es erneut.');
            });
        }

        // Start the countdown display
        function startCountdown() {
            // Hide timer button, show running timer
            document.getElementById('timerButton').style.display = 'none';
            document.getElementById('timerRunning').style.display = 'block';
            
            // Update display immediately
            updateTimerDisplay();
            
            // Update every second
            timerInterval = setInterval(function() {
                remainingSeconds--;
                updateTimerDisplay();
                
                if (remainingSeconds <= 0) {
                    timerComplete();
                }
            }, 1000);
        }

        // Update the timer display
        function updateTimerDisplay() {
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            const display = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            document.getElementById('timerDisplay').textContent = display;
        }

        // Stop timer manually
        function stopTimer() {
            if (!currentTimerId) {
                return;
            }
            
            // Stop the countdown
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            
            // Update database
            fetch('{{ route('admin.timer.stop') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    timer_id: currentTimerId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resetTimerUI();
                } else {
                    console.error('Failed to stop timer:', data.message);
                    resetTimerUI(); // Reset UI anyway
                }
            })
            .catch(error => {
                console.error('Error stopping timer:', error);
                resetTimerUI(); // Reset UI anyway
            });
        }

        // Timer completed (reached zero)
        function timerComplete() {
            // Stop the countdown
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            
            // Play bell sound
            const bellSound = document.getElementById('timerBellSound');
            if (bellSound) {
                bellSound.play().catch(error => {
                    console.error('Error playing bell sound:', error);
                });
            }
            
            // Update database
            fetch('{{ route('admin.timer.complete') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    timer_id: currentTimerId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resetTimerUI();
                    
                } else {
                    console.error('Failed to complete timer:', data.message);
                    resetTimerUI();
                }
            })
            .catch(error => {
                console.error('Error completing timer:', error);
                resetTimerUI();
            });
        }

        // Reset timer UI to initial state
        function resetTimerUI() {
            document.getElementById('timerButton').style.display = 'block';
            document.getElementById('timerRunning').style.display = 'none';
            document.getElementById('timerDisplay').textContent = '00:00';
            currentTimerId = null;
            remainingSeconds = 0;
        }

        // Check for active timer on page load
        function checkActiveTimer() {
            fetch('{{ route('admin.timer.active') }}', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.timer) {
                    currentTimerId = data.timer.id;
                    remainingSeconds = data.timer.remaining;
                    
                    if (remainingSeconds > 0) {
                        startCountdown();
                    } else {
                        // Timer already expired, complete it
                        timerComplete();
                    }
                }
            })
            .catch(error => {
                console.error('Error checking active timer:', error);
            });
        }

        // Check for active timer when page loads
        document.addEventListener('DOMContentLoaded', function() {
            checkActiveTimer();
        });
        // ==================== END TIMER FUNCTIONALITY ====================

    </script>
@endsection
