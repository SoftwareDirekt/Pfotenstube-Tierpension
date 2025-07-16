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
                                                                <div>
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
                                                                $days_between = (int)(abs(strtotime($obj->checkout_date) - strtotime($obj->checkin_date)) / 86400);

                                                                if($days_between == 0)
                                                                {
                                                                    $days_between = 1;
                                                                }
                                                            @endphp
                                                            <div class="flex justify-between py-2">
                                                                <p class="tag">{{$days_between}} Tage</p>
                                                                @if($obj->totalAmount > 0)
                                                                    <p class="text-success">
                                                                        {{number_format($obj->totalAmount, 2)}}&euro;</p>
                                                                @else
                                                                    <p class="text-danger">
                                                                        {{number_format($obj->totalAmount, 2)}}&euro;</p>
                                                                @endif
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
                                                                <div class="flex justify-between">
                                                                    <p>
                                                                        ID{{str_pad($item->dog_id, 3, '0', STR_PAD_LEFT)}}</p>
                                                                    <p>{{$item->stays}}</p>
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
                                                                            @if($item->totalAmount > 0)
                                                                                <p class="text-success bjty">
                                                                                    {{number_format($item->totalAmount, 2)}}&euro;</p>
                                                                            @else
                                                                                <p class="text-danger bjty">
                                                                                    {{number_format($item->totalAmount, 2)}}&euro;</p>
                                                                            @endif
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
                                                                <p class="pb-1 px-2 py-2">
                                                                    <a href="{{route('admin.customers.preview', ['id' => $item->dog->customer->id])}}">{{$item->dog->customer->name}}
                                                                        ({{$item->dog->customer->id_number}})</a>
                                                                </p>
                                                                <hr class="p-0 m-0">
                                                                @php

                                                                    $days_between = (int)(abs(strtotime($item->checkout_date) - strtotime($item->checkin_date)) / 86400);

                                                                    if($days_between == 0)
                                                                    {
                                                                      $days_between = 1;
                                                                    }

                                                                  //   $eating_habits = json_decode($item->dog->eating_habits, true);
                                                                  //   if (is_array($eating_habits)) {
                                                                        $morning = $item->dog->eating_morning != '' ? '1' : '0'; // Ensure these are strings
                                                                        $afternoon = $item->dog->eating_midday != '' ? '1' : '0'; // Ensure these are strings
                                                                        $evening = $item->dog->eating_evening != '' ? '1' : '0'; // Ensure these are strings
                                                                        $bf = ($item->dog->is_special_eating == 0) ? '' : 'BF ';

                                                                        $eating_habits = $bf . $morning . '-' . $afternoon . '-' . $evening;

                                                                  //   } else {
                                                                  //       $morning = 0;
                                                                  //       $afternoon = 0;
                                                                  //       $evening = 0;
                                                                  //       $eating_habits = "0-0-0";
                                                                  //       $bf = '';
                                                                  //   }
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
                                    <select name="dog_ids[]" id="dog_id" class="select2"
                                            data-placeholder='Wählen Sie Hunde aus' data-live-search="true" required>
                                        <option selected disabled>Hund auswählen</option>
                                        @foreach($dogs as $dog)
                                            <option value="{{$dog->id}}">{{$dog->name}} @if($dog->compatible_breed)
                                                    ({{$dog->compatible_breed}})
                                                @endif - {{isset($dog->customer) ? $dog->customer->name : ''}}
                                                ({{ isset($dog->customer) ? $dog->customer->phone : '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="dog_id">Hund</label>
                                </div>
                                <input type="hidden" name="is_dashboard" value="1">
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
                            <input type="hidden" class="form-control" name="id" id="id"/>
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
                            <input type="hidden" class="form-control" name="id" id="id"/>
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
                                                    {{-- <button id="monatsplan_button" class="btn btn-info btn-sm" onclick="checkEventShift({{ $obj->id }})">
                                                      Monatsplan
                                                    </button> --}}
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
                                <input type="hidden" name="room_id" id="id" class="form-control">
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

    {{----plan model---}}
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
                    <form action="{{route('admin.move.dog')}}" method="POST" enctype="multipart/form-data">
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

    {{-- Dog Information Modal --}}
    <div class="modal fade" id="dogInfo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-body p-md-0">
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

                    <div class="row">

                        <div class="col-md-8">
                            <table class="table table-striped text-cente infoModalHabitsTable"
                                   style="width: 100%; margin-left:0;">
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
                                                width="120" class="rounded img-fluid" alt="Friend Picture"/>
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
                        <div class="col-md-4">
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
                            {{-- Dog Friends Area --}}
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
                            <div class="col-md-12">
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
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="gateway" id="gateway" class="select2" data-live-search="true"
                                            required>
                                        <option value="Bar">Bar</option>
                                        <option value="Bank">Banküberweisung</option>
                                        <option value="Nicht bezahlt">Nicht bezahlt</option>
                                    </select>
                                    <label for="gateway">Zahlungsart</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="status" id="status" class="select2" data-live-search="true" required>
                                        <option value="1" selected>Bezahlt</option>
                                        <option value="0">Nicht bezahlt</option>
                                        <option value="2">Offen</option>
                                    </select>
                                    <label for="status">Status</label>
                                </div>
                            </div>
                            <div class="col-md-12">
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
                            <div class="col-md-12 mt-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" id="cost" class="form-control text-primary" value="330.00"
                                           disabled/>
                                    <label for="gateway">Aktueller Preis (&euro;)</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" name="total" step="0.10" id="total_amount"
                                           class="form-control text-primary absInput" value="330.00"/>
                                    <label for="total_amount">Rechnungsbetrag (&euro;)</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" name="received_amount" step="0.10" id="received_amount"
                                           class="form-control text-primary absInput" value="330.00"/>
                                    <label for="received_amount">Betrag Erhalten (&euro;)</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-4 d-flex justify-content-end">
                                <div>
                                    <p style="font-size: 17px">
                                        <strong>Kundenguthaben: </strong> <span id="saldo">-400</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="id" id="id"/>
                        <input type="hidden" name="days" id="days"/>
                        <input type="hidden" name="checkout" id="checkout_date"/>
                        <button type="submit" class="btn btn-primary">Kassa</button>
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

    {{-- friendshipModal --}}
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

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsModalLabel">Ereignisdetails</h5> <!-- Event Details -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    <!-- Close -->
                </div>
                <div class="modal-body">
                    <p><strong>Startzeit:</strong> <span id="eventStartTime"></span></p> <!-- Start Time -->
                    <p><strong>Endzeit:</strong> <span id="eventEndTime"></span></p> <!-- End Time -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    <!-- Close -->
                </div>
            </div>
        </div>
    </div>


    {{-- Modal Area Ends Here --}}
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
                url: '{{ route("admin.validate.pin") }}',  // Route to your Laravel controller
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

        $(window).on('load', function () {
            document.getElementById('togglerMenuBy').click();

            // $("#dogsInRoom #id_dog").select2({
            //   dropdownCssClass: 'big-drop'
            // });

        });


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
            $("#deleteReservation #id").val(id);
            $("#deleteReservation").modal('show');
        }

        //check monatsplan event

        function checkEventShift(uid) {
            $.ajax({
                url: "{{ route('admin.check.event.shift') }}", // Backend route
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}", // CSRF token
                    uid: uid // Pass user ID
                },
                success: function (response) {
                    if (response.exists) {
                        // Close any existing modal
                        $('.modal').modal('hide');

                        // Set event details in the modal
                        $('#eventStartTime').text(response.event.start);
                        $('#eventEndTime').text(response.event.end);

                        $('#eventDetailsModal').modal('show');
                    } else {
                        alert("Kein Ereignis für Sie gefunden.");
                    }
                },
                error: function (xhr, status, error) {
                    alert("An error occurred: " + error);
                }
            });
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
                                style="width: 30px; height: 30px; border: 1px solid #007bff;">
                        </div>`;

                            $('#profilePicturesContainer').append(profilePicElement);
                        } else {
                            const firstLetter = response.user.name.charAt(0).toUpperCase();
                            const nameElement = `
                        <div id="profile_pic_${employeeId}" class="me-2 d-flex justify-content-center align-items-center"
                            style="width: 30px; height: 30px; border: 1px solid #007bff; border-radius: 50%; background-color: #007bff; color: white;">
                            <span>${firstLetter}</span>
                        </div>`;

                            $('#profilePicturesContainer').append(nameElement);
                        }
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('An error occurred while creating the event.');
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
                                    style="width: 30px; height: 30px; border: 1px solid #007bff;">
                            </div>`;

                                // Remove any existing profile pic and append the new one
                                $(`#profile_pic_${uid}`).remove();
                                $('#profilePicturesContainer').append(profilePicElement);
                            } else {
                                const firstLetter = user.name.charAt(0).toUpperCase();
                                const nameElement = `
                            <div id="profile_pic_${uid}" class="me-2 d-flex justify-content-center align-items-center"
                                style="width: 30px; height: 30px; border: 1px solid #007bff; border-radius: 50%; background-color: #007bff; color: white;cursor:pointer;">
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
                    alert('An error occurred while trying to end the event.');
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
            $("#deleteTodo #id").val(id);
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
            $("#dogsInRoom #id").val(id);
            $("#dogsInRoom").modal('show');
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
                                    html += `<td><img src="uploads/users/dogs/${item.dog.picture}" width="120" class="rounded img-fluid" alt="Friend Picture" /></td>`;
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
                            var html = "<tr><td colspan='4' class='text-center'>No records found</td></tr>";
                            $("#hundeFriends tbody").html(html);
                        }

                        // Checkin/out dates
                        $("#dogInfo #rangepicker").val(res.res_date);
                        $("#dogInfo #note").val(res.dog.note);
                        $("#dogInfo #note_id").val(res.dog.id);
                        $("#dogInfo #res_id").val(res.id);

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
                alert('Dieser Hund ist bereits eingecheckt und kann nicht ein weiteres mal einchecken')
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
                        var saldo = res.total;
                        var doc = res.doc;

                        var checkin_date = new Date(checkin);
                        var checkout_date = new Date(res.checkout_date);

                        var daysDiffs = checkout_date.getTime() - checkin_date.getTime();
                        var daysDiff = Math.floor(daysDiffs / (1000 * 3600 * 24));
                        var tage = (daysDiff > 0) ? daysDiff : 1;

                        var today = new Date();
                        var time_difference = today.getTime() - checkin_date.getTime();
                        var difference_in_days = Math.floor(time_difference / (1000 * 3600 * 24));
                        var days = (difference_in_days > 0) ? difference_in_days : 1;

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

                        if (days > 1) {
                            var total_cost = (days * plan_price);
                        } else {
                            console.log(doc)
                            var total_cost = doc.dog.day_plan_obj.price;
                        }

                        $('#checkoutModal #saldo').text(saldo + '€');
                        var intSaldo = parseInt(saldo);
                        if (intSaldo < 0) {
                            $('#checkoutModal #saldo').addClass('text-danger')
                            $('#checkoutModal #saldo').removeClass('text-success')
                        } else if (intSaldo > 0) {
                            $('#checkoutModal #saldo').removeClass('text-danger')
                            $('#checkoutModal #saldo').addClass('text-success')
                        } else {
                            $('#checkoutModal #saldo').removeClass('text-danger')
                            $('#checkoutModal #saldo').removeClass('text-success')
                        }

                        $('#checkoutModal #id').val(id);
                        $('#checkoutModal #days').val(days);
                        $('#checkoutModal #checkin').html(formatted_checkin_date);
                        $('#checkoutModal #checkout').html(formatted_checkout_date + ` (${days} Tage)`);
                        $('#checkoutModal #checkout_date').val(formatted_checkout_date);

                        if (days > 1) {
                            $('#checkoutModal #price_plan').val(doc.dog.reg_plan_obj.id).trigger('change');
                        } else {
                            $('#checkoutModal #price_plan').val(doc.dog.day_plan_obj.id).trigger('change');
                        }

                        $('#checkoutModal #cost').val(total_cost);
                        $('#checkoutModal #total_amount').val(total_cost);
                        $('#checkoutModal #received_amount').val(total_cost);

                        $('#checkoutModal').modal('show');
                    }
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

        fetchTodos();

        $(document).ready(function () {
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

            // Change negative values to postive
            $(".absInput").on('change', function () {
                this.value = Math.abs(this.value);
            });

            // Check price change on checkout
            $("#checkoutModal #price_plan").on('change', function () {
                var days = $("#checkoutModal #days").val();
                var plans = @json($plans);
                var plan_id = this.value;
                var plan = plans.find(function (plan) {
                    return plan.id == plan_id;
                });
                var price = plan.price;
                var total = days * price;
                var total_cost = total;

                // check for discounts
                var discount = $("#checkoutModal input[name='discount']:checked").val();
                discount = parseInt(discount);
                if (discount > 0) {
                    total = total * (1 - (discount / 100));
                }

                $('#checkoutModal #cost').val(total_cost);
                $('#checkoutModal #total_amount').val(total);
                $('#checkoutModal #received_amount').val(total);
            });

            // Discount change on checkout
            $("#checkoutModal input[name='discount']").on('change', function () {
                var discount = parseInt(this.value);
                var total = $("#checkoutModal #cost").val();
                total = parseFloat(total);
                if (discount > 0) {
                    total = total * (1 - (discount / 100));
                }
                $('#checkoutModal #total_amount').val(total);
                $('#checkoutModal #received_amount').val(total);
            })

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
                console.log(idBeingDragged);
                if (ui.item.parents().parents().parents().attr('id') === 'parent_res') {
                    if ($('.scrumboard').find('#' + idBeingDragged).length > 0) {
                        alert('Dieser Hund ist bereits eingecheckt und kann nicht ein weiteres mal einchecken')
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
                if (typeof dogs[dogID] === 'undefined') {
                    // console.log(dogs[dogID])
                    console.log("reservation");
                    // var capacity = parseInt(rooms[getRoom]['capacity']);
                    // var reserved = parseInt(rooms[getRoom]['reserved']);
                    var room = rooms.find((item, index) => {
                        return item.id == getRoom;
                    });

                    var available_dogs = $("#parent_" + getRoom + ' .dogItems .child');
                    var capacity = parseInt(room.capacity);
                    var reserved = available_dogs.length;

                    // dogs[dogID] = dogsR[dogID];
                    // delete dogsR[dogID];

                    if (reserved >= capacity) {
                        $(this).sortable('cancel');
                    } else {
                        $.ajax({
                            url: "{{route('admin.reservation.room.update')}}",
                            type: 'POST',
                            data: {
                                _token: "{{csrf_token()}}",
                                id: reservationID,
                                room_id: getRoom,
                                status: 1
                            },
                            dataType: "json",
                            success: function (data) {
                                console.log(data)
                                // rooms.map((itemD) => {
                                //   itemD.reservations.find((fnd) => {
                                //     if(fnd.id == reservationID)
                                //     {
                                //       fnd.room_id = getRoom;
                                //     }
                                //   })
                                // });

                                // dogs[dogID].room_id = getRoom;
                                updateRoomsCount()
                                // $(".accept_friend").attr("data-room", getRoom)
                                // $(".accept_friend").attr("data-dog", dogID)
                                // if(data.become_friends){
                                //     $("#becomeFriends").modal("show")
                                // }
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
                        $(this).sortable('cancel');
                    } else {
                        // Update reservation room_id
                        console.log(getRoom)
                        console.log(dogID)
                        $.ajax({
                            url: "{{route('admin.reservation.room.update')}}",
                            type: 'POST',
                            data: {
                                _token: "{{csrf_token()}}",
                                id: reservationID,
                                room_id: getRoom,
                                dog_id: dogID
                            },
                            dataType: "json",
                            success: function (data) {
                                rooms.map((itemD) => {
                                    itemD.reservations.find((fnd) => {
                                        if (fnd.id == reservationID) {
                                            fnd.room_id = getRoom;
                                        }
                                    })
                                });

                                dogs[dogID].room_id = getRoom;
                                updateRoomsCount();
                                if (data.showModal == true) {
                                    console.log('open')
                                    $("#friendshipModal #dog_id").val(dogID);
                                    $("#friendshipModal #room_id").val(getRoom);
                                    $("#friendshipModal").modal('show');
                                }
                                // $(".accept_friend").attr("data-room", getRoom)
                                // $(".accept_friend").attr("data-dog", dogID)
                                // if(data.become_friends){
                                //     $("#becomeFriends").modal("show")
                                // }
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

        });

        fetchEventStatuses();

    </script>
@endsection
