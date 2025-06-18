@extends('admin.layouts.app')
@section('title')
    <title>Hund bearbeiten</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
<link rel="stylesheet" href="assets/vendor/libs/bootstrap-select/bootstrap-select.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header ">
              <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-0">Hund bearbeiten</h5>
                </div>
                @if($dog->picture != null)
                <div class="col-md-12 my-3">
                    <img src="uploads/users/dogs/{{$dog->picture}}" width="150" height="150" style="border-radius: 5px" class="img-fluid" alt="Dog">
                </div>
                @endif
              </div>
            </div>
            <div class="card-body">
              <form action="{{route('admin.customers.update_dog')}}" method="POST" enctype="multipart/form-data" class="custColors">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="customer_id" id="customer_id" class="form-control" required disabled>
                                @foreach($customers as $obj)
                                <option value="{{$obj->id}}" {{ ($dog->customer_id == $obj->id) ? 'selected': '' }}>
                                    {{$obj->name}} ({{$obj->phone}})
                                </option>
                                @endforeach
                            </select>
                            <label for="customer_id">Kunde</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="name" id="name" value="{{$dog->name}}" placeholder="Name" required />
                            <label for="name">Name</label>
                        </div>
                        @error('name')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-gray fw-semibold">Geschlecht:</h6>
                        <div class="d-flex">
                            <div class="form-check mb-4">
                                <input type="radio" class="form-check-input" name="gender" id="male" value="männlich" {{ $dog->gender == 'männlich' ? 'checked' : '' }} />
                                <label for="male">männlich</label>
                            </div>
                            <div class="form-check mb-4 mx-5">
                                <input type="radio" class="form-check-input" name="gender" id="female" value="weiblich" {{ $dog->gender == 'weiblich' ? 'checked' : '' }}  />
                                <label for="female">weiblich</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-gray fw-semibold">Kompatibilität:</h6>
                        <div class="d-flex">
                            <div class="form-check mb-4">
                                <input type="radio" class="form-check-input" name="compatibility" id="compatible" value="V" {{ $dog->compatibility == 'V' ? 'checked' : '' }}/>
                                <label for="compatible">Verträglich</label>
                            </div>
                            <div class="form-check mb-4 mx-3">
                                <input type="radio" class="form-check-input" name="compatibility" id="incompetible" value="UV" {{ $dog->compatibility == 'UV' ? 'checked' : '' }} />
                                <label for="incompetible">Unverträglich</label>
                            </div>
                            <div class="form-check mb-4">
                                <input type="radio" class="form-check-input" name="compatibility" id="compatibleJ" value="VJ" {{ $dog->compatibility == 'VJ' ? 'checked' : '' }}/>
                                <label for="compatibleJ">V mit Jungs</label>
                            </div>
                            <div class="form-check mb-4 mx-3">
                                <input type="radio" class="form-check-input" name="compatibility" id="compatibleM" value="VM" {{ $dog->compatibility == 'VM' ? 'checked' : '' }}/>
                                <label for="compatibleM">V mit Mädls</label>
                            </div>
                            <div class="form-check mb-4 mx-3">
                                <input type="radio" class="form-check-input" name="compatibility" id="compatibleS" value="S" {{ $dog->compatibility == 'S' ? 'checked' : '' }}/>
                                <label for="compatibleS">Sympatie</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-5">
                             <div class="d-flex">
                                 <div class="form-check mb-4">
                                     <input type="checkbox" class="form-check-input" name="is_allergy" id="is_allergy" value="1" {{ $dog->is_allergy == '1' ? 'checked' : '' }} onchange="toggleAllergyInput()" />
                                     <label for="is_allergy">Allergiker</label>
                                 </div>
                             </div>
                             
                             <div class="d-flex">
                                 <div class="form-check mb-4">
                                     <input type="checkbox" class="form-check-input" name="water_lover" id="water_lover" value="1" {{ $dog->water_lover == '1' ? 'checked' : '' }}/>
                                     <label for="water_lover">Wassergräber</label>
                                 </div>
                             </div>
                         </div>
                         <div class='mb-2 form-floating form-floating-outline {{ $dog->is_allergy == '1' ? '' : 'd-none' }}' id='allergyBox'>
                             <input type='text' placeholder='Allergie..' name="allergy" class='form-control' value="{{$dog->allergy}}"/>
                             <label>Allergie</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="file" class="form-control" name="picture" id="picture" placeholder="Bild"/>
                            <label for="picture">Bild</label>
                        </div>
                        @error('picture')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="date" class="form-control" name="age" id="age" value="{{$dog->age}}" placeholder="Alter"/>
                            <label for="age">Alter</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="neutered" id="neutered" class="form-control">
                                <option {{ $dog->neutered == '1' ? 'selected' : '' }} value="1">Ja</option>
                                <option {{ $dog->neutered == '0' ? 'selected' : '' }} value="0">Nein</option>
                            </select>
                            <label for="neutered">Kastriert</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="race" id="race" value="{{$dog->compatible_breed}}" placeholder="Rasse" />
                            <label for="race">Rasse</label>
                        </div>
                    </div>


                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="chip_number" id="chip_number" value="{{$dog->chip_number}}" placeholder="Chipnummer"/>
                            <label for="chip_number">Chipnummer</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="health_problems" id="health_problems" value="{{$dog->health_problems}}" placeholder="Gesundheitsprobleme" />
                            <label for="health_problems">Gesundheitsprobleme</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="number" class="form-control" name="visits" id="visits" value="{{$dog->visit->visits}}" placeholder="Anzahl an Besuchen"/>
                            <label for="visits">Anzahl an Besuchen</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="number" class="form-control" name="days" id="days" value="{{$dog->visit->stay }}" placeholder="wie viele Tage"/>
                            <label for="days">wie viele Tage</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="number" class="form-control" name="weight" id="weight" value="{{$dog->weight}}" placeholder="Gewicht"/>
                            <label for="weight">Gewicht</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="price_plan" id="price_plan" class="select2 form-control" required>
                                <option selected disabled>Wählen Sie Preispläne</option>
                                @foreach($plans as $obj)
                                <option value="{{$obj->id}}" {{$dog->reg_plan == $obj->id ? 'selected' : ''}} ><?php echo $obj->title; ?></option>
                                @endforeach
                            </select>
                            <label for="price_plan">Pensionstarif</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="daily_rate" id="daily_rate" class="select2 form-control" required>
                                <option selected disabled>Wählen Sie Preispläne</option>
                                @foreach($plans as $obj)
                                <option value="{{$obj->id}}" {{$dog->day_plan == $obj->id ? 'selected' : ''}} ><?php echo $obj->title; ?></option>
                                @endforeach
                            </select>
                            <label for="daily_rate">Tagestarif</label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3 my-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_medication" id="medication" {{ $dog->is_medication == '1' ? 'checked' : '' }} onchange="toggleMedics()"/>
                                    <label for="medication">Medikamente</label>
                                </div>
                            </div>

                            <div class="col-md-3 mb-4 {{ $dog->is_medication == '1' ? '' : 'd-none' }}" id="morgen_box">
                                <h6 class="text-gray fw-semibold">Morgen</h6>
                                <input type="text" class="form-control" name="morgen" value="{{$dog->morgen}}" placeholder="Morgen" />
                            </div>
                            <div class="col-md-3 mb-4 {{ $dog->is_medication == '1' ? '' : 'd-none' }}" id="mittag_box">
                                <h6 class="text-gray fw-semibold">Mittag</h6>
                                <input type="text" class="form-control" name="mittag" value="{{$dog->mittag}}" placeholder="Mittag" />
                            </div>
                            <div class="col-md-3 mb-4 {{ $dog->is_medication == '1' ? '' : 'd-none' }}" id="abend_box">
                                <h6 class="text-gray fw-semibold">Abend</h6>
                                <input type="text" class="form-control" name="abend" value="{{$dog->abend}}" placeholder="Abend" />
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3 my-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_eating_habits" id="is_eating_habits" onchange="toggleEatingHabits()" {{$dog->is_eating_habits == 1 ? 'checked' : ''}} />
                                    <label for="is_eating_habits">Essgewohnheiten</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4 {{$dog->is_eating_habits == 1 ? '' : 'd-none'}}" id="eating_morgen_box">
                                <h6 class="text-gray fw-semibold">Morgen</h6>
                                <input type="text" class="form-control" name="eating_morning" value="{{$dog->eating_morning}}" placeholder="Morgen" />
                            </div>
                            <div class="col-md-3 mb-4 {{$dog->is_eating_habits == 1 ? '' : 'd-none'}}" id="eating_mittag_box">
                                <h6 class="text-gray fw-semibold">Mittag</h6>
                                <input type="text" class="form-control" name="eating_midday" value="{{$dog->eating_midday}}" placeholder="Mittag" />
                            </div>
                            <div class="col-md-3 mb-4 {{$dog->is_eating_habits == 1 ? '' : 'd-none'}}" id="eating_abend_box">
                                <h6 class="text-gray fw-semibold">Abend</h6>
                                <input type="text" class="form-control" name="eating_evening" value="{{$dog->eating_evening}}" placeholder="Abend" />
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3 my-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_special_eating" id="is_special_eating" onchange="toggleSpecialFeeding()" {{$dog->is_special_eating == 1 ? 'checked' : ''}} />
                                    <label for="is_special_eating">Besondere Fütterung</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4 {{$dog->is_special_eating == 1 ? '' : 'd-none'}}" id="special_morgen_box">
                                <h6 class="text-gray fw-semibold">Morgen</h6>
                                <input type="text" class="form-control" name="special_morning" value="{{$dog->special_morning}}" placeholder="Morgen" />
                            </div>
                            <div class="col-md-3 mb-4 {{$dog->is_special_eating == 1 ? '' : 'd-none'}}" id="special_mittag_box">
                                <h6 class="text-gray fw-semibold">Mittag</h6>
                                <input type="text" class="form-control" name="special_midday" value="{{$dog->special_midday}}" placeholder="Mittag" />
                            </div>
                            <div class="col-md-3 mb-4 {{$dog->is_special_eating == 1 ? '' : 'd-none'}}" id="special_abend_box">
                                <h6 class="text-gray fw-semibold">Abend</h6>
                                <input type="text" class="form-control" name="special_evening" value="{{$dog->special_evening}}" placeholder="Abend" />
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-4">
                    <input type="hidden" class="form-control" name="id" value="{{$dog->id}}">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                    <a href="{{route('admin.customers.preview' , ['id' => $dog->customer_id])}}">
                        <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                    </a>
                </div>
              </form>
            </div>
            <hr>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Abholung des Hundes</h5>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        <button type="button" class="no-style" style="color: #5a5fe0" data-bs-toggle="modal" data-bs-target="#addPickupModal">
                            <i class="fa fa-plus"></i>
                            Weitere hinzufügen
                        </button>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Surname</th>
                                <th>Telefonnummer</th>
                                <th>ID-Number</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dog->pickups as $obj)
                            <tr>
                                <td>
                                    @if($obj->picture !== null)
                                    <img src="uploads/users/pickup/{{$obj->picture}}" width="35" height="35" class="rounded" alt="Picture" />
                                    @endif
                                    {{$obj->name}}
                                </td>
                                <td>{{$obj->phone}}</td>
                                <td>{{$obj->id_number}}</td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <div>
                                            <button class="no-style actionBtn" onclick="editPickupModal('{{$obj}}')">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </div>
                                        {{-- @if($loop->index > 0) --}}
                                        <div>
                                            <button class="no-style actionBtn" onclick="deletePickup('{{$obj->id}}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                        {{-- @endif --}}
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <hr>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Hundefreunde</h5>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        <button type="button" class="no-style" style="color: #5a5fe0" data-bs-toggle="modal" data-bs-target="#addFriendsModal">
                            <i class="fa fa-plus"></i>
                            Freund hinzufügen
                        </button>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Freunde</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dog->friends as $obj)
                            @php if(!isset($obj->dog)){continue;} @endphp
                            <tr>
                                <td>
                                    @if(isset($obj->dog->picture))
                                    <img src="uploads/users/dogs/{{$obj->dog->picture}}" width="80" height="80" class="rounded" alt="Picture" />
                                    @endif
                                    {{$obj->dog->name}} ({{$obj->dog->id}})
                                </td>
                                <td>
                                    Freund seit {{date('d M, Y', strtotime($obj->created_at))}}
                                </td>
                                <td class="text-end">
                                    <div>
                                        <button class="no-style text-danger" onclick="removeFriend('{{$obj->id}}')">
                                            <i class="mdi mdi-close-circle-outline"></i> Entfernen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Pickup Modal --}}
<div class="modal fade" id="addPickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-xl modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="mb-2 pb-1">Abholung hinzufügen</h3>
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                    <button type="button" class="no-style" style="color: #5a5fe0" onclick="addPickup()">
                        <i class="fa fa-plus"></i>
                        Weitere hinzufügen
                    </button>
                </div>
            </div>
          </div>
          <form class="row g-3" method="POST" action="{{route('admin.customers.add.pickups')}}" enctype="multipart/form-data">
            @csrf
            <div class="addPickupWrapper" id="addPickupWrapper">
                <div id="pickupItem0" class="item">
                    <div class="row">
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="text" class="form-control" name="pick_name[]" id="pick_name0" placeholder="Name" required/>
                                <label for="pick_name0">Name</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="number" class="form-control" name="pick_phone[]" id="pick_phone0" placeholder="Telefonnummer" required/>
                                <label for="pick_phone0">Telefonnummer</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="text" class="form-control" name="pick_id[]" id="pick_id0" placeholder="ID-Number"/>
                                <label for="pick_id0">ID-Number</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="file" class="form-control" name="pick_file[]" id="pick_file0" placeholder="Bild"/>
                                <label for="pick_file0">Bild</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="col-12 d-flex ">
                <input type="hidden" name="id" id="id" value="{{$dog->id}}"/>
                <button type="submit" class="btn btn-primary me-sm-3 me-1">
                    Einreichen
                </button>
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

{{-- Edit Pickup Modal --}}
<div class="modal fade" id="editPickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-xl modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="mb-4">
              <h3 class="mb-2 pb-1">Abholung bearbeiten</h3>
          </div>
          <form class="row g-3" method="POST" action="{{route('admin.customers.update.pickups')}}" enctype="multipart/form-data">
            @csrf
            <div class="addPickupWrapper" id="addPickupWrapper">
                <div id="pickupItem0" class="item">
                    <div class="row">
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Name" required/>
                                <label for="name">Name</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="number" class="form-control" name="phone" id="phone" placeholder="Telefonnummer" required/>
                                <label for="phone">Telefonnummer</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="text" class="form-control" name="id_number" id="id_number" placeholder="ID-Number"/>
                                <label for="id_number">ID-Number</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class='form-floating form-floating-outline mb-4'>
                                <input type="file" class="form-control" name="file" id="file" placeholder="Bild"/>
                                <label for="file">Bild</label>
                            </div>
                        </div>
                        <div id="photo"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="col-12 d-flex ">
                <input type="hidden" name="id" id="id" value="{{$dog->id}}"/>
                <button type="submit" class="btn btn-primary me-sm-3 me-1">Aktualisieren</button>
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

{{-- Delete Pickup Modal --}}
<div class="modal fade" id="deletePickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Abholung löschen</h3>
          </div>
          <p class="text-center">
            Sind Sie sicher, dass Sie diesen Datensatz löschen möchten?
          </p>
          <form class="row g-3" method="POST" action="{{route('admin.customers.pickup.delete')}}">
            @csrf
            <div class="col-12 d-flex justify-content-center">
                <input type="hidden" name="id" id="id" />
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

{{-- Remove Friend Modal --}}
<div class="modal fade" id="removeFriend" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Freund entfernen</h3>
          </div>
          <p class="text-center">
            Möchten Sie den Hund wirklich aus der Freundesliste entfernen?
          </p>
          <form class="row g-3" method="POST" action="{{route('admin.dog.remove.friend')}}">
            @csrf
            <div class="col-12 d-flex justify-content-center">
                <input type="hidden" name="id" id="id" />
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

{{-- Add Friends Modal --}}
<div class="modal fade" id="addFriendsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-xl modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="mb-4">
            <h3 class="mb-2 pb-1">Neue Freunde hinzufügen</h3>
          </div>
          <form class="row g-3" method="POST" action="{{route('admin.dog.friends.add')}}" enctype="multipart/form-data">
            @csrf
            <div class="form-floating form-floating-outline mb-4">
                <select
                  id="dog_friends"
                  name="dog_friends[]"
                  class="selectpicker w-100"
                  data-style="btn-default"
                  data-live-search="true" multiple>
                  @foreach ($dogs as $obj)
                    @if(isset($obj->customer))
                    @if($obj->id != $dog->id && !in_array($obj->id, $dog->friend_ids))
                    <option data-tokens="ketchup " value="{{$obj->id}}" {{ in_array($obj->id, $dog->friend_ids) ? 'selected' : '' }} >
                        <img src="uploads/users/dogs/{{$obj->picture}}" alt="#">
                        {{ $obj->name }} ({{$obj->customer->name}} - {{$obj->customer->phone }})
                    </option>
                    @endif
                    @endif
                  @endforeach
                </select>
                <label for="dog_friends">Hunde Freunde</label>
            </div>
            <hr>
            <div class="col-12 d-flex ">
                <input type="hidden" name="id" id="id" value="{{$dog->id}}"/>
                <button type="submit" class="btn btn-primary me-sm-3 me-1">
                    Einreichen
                </button>
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

@endsection
@section('extra_js')
<script src="assets/vendor/libs/bootstrap-select/bootstrap-select.js"></script>
<script src="assets/vendor/libs/select2/select2.js"></script>

<script>
    function addPickup()
    {
        var id = $('#addPickupWrapper').children('.item').length;

        let html = "<div id='pickupItem"+id+"' class='item'>";
            html += "<div class='d-flex justify-content-end my-2'><button onclick='removePickup("+id+")' class='no-style text-danger'><i class='fa fa-close'></i> Weitere entfernen</button></div>";
            html += "<div class='row'>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="text" class="form-control" name="pick_name[]" id="pick_name'+id+'" placeholder="Name" required/><label for="pick_name'+id+'">Name</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="number" class="form-control" name="pick_phone[]" id="pick_phone'+id+'" placeholder="Telefonnummer" required/><label for="pick_phone'+id+'">Telefonnummer</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3   '>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="text" class="form-control" name="pick_id[]" id="pick_id'+id+'" placeholder="ID-Number"/><label for="pick_id'+id+'">ID-Number</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="file" class="form-control" name="pick_file[]" id="pick_file'+id+'" placeholder="Bild"/><label for="pick_file'+id+'">Bild</label>';
                html += "</div>";
            html += "</div>";
        html += "</div></div>";

        $("#addPickupWrapper").append(html)
    }

    function removePickup(id)
    {
        $("#pickupItem"+id).remove();
    }

    function deletePickup(id)
    {
        $("#deletePickupModal #id").val(id);
        $("#deletePickupModal").modal('show');
    }

    function editPickupModal(obj)
    {
        obj = JSON.parse(obj)

        $("#editPickupModal #id").val(obj.id);
        $("#editPickupModal #name").val(obj.name);
        $("#editPickupModal #phone").val(obj.phone);
        $("#editPickupModal #id_number").val(obj.id_number);
        if(obj.picture !== null)
        {
            let html = '<div class="col-md-12"><img src="uploads/users/pickup/'+obj.picture+'" width="100" height="100" class="rounded" ></div>';
            $("#editPickupModal #photo").html(html);
        }
        $("#editPickupModal").modal('show');
    }

    function removeFriend(id)
    {
        $("#removeFriend #id").val(id);
        $("#removeFriend").modal('show');
    }

    function toggleAllergyInput()
    {
        if($(`#is_allergy`).is(':checked'))
        {
            $(`#allergyBox`).removeClass('d-none');
        }
        else{
            $(`#allergyBox`).addClass('d-none');
        }
    }
    function toggleMedics()
    {
        if($(`#medication`).is(':checked'))
        {
            $(`#morgen_box`).removeClass('d-none');
            $(`#mittag_box`).removeClass('d-none');
            $(`#abend_box`).removeClass('d-none');
        }else{
            $(`#morgen_box`).addClass('d-none');
            $(`#mittag_box`).addClass('d-none');
            $(`#abend_box`).addClass('d-none');
        }
    }
    function toggleEatingHabits()
    {
        if($(`#is_eating_habits`).is(':checked'))
        {
            $(`#eating_morgen_box`).removeClass('d-none');
            $(`#eating_mittag_box`).removeClass('d-none');
            $(`#eating_abend_box`).removeClass('d-none');
        }else{
            $(`#eating_morgen_box`).addClass('d-none');
            $(`#eating_mittag_box`).addClass('d-none');
            $(`#eating_abend_box`).addClass('d-none');
        }
    }
    function toggleSpecialFeeding()
    {
        if($(`#is_special_eating`).is(':checked'))
        {
            $(`#special_morgen_box`).removeClass('d-none');
            $(`#special_mittag_box`).removeClass('d-none');
            $(`#special_abend_box`).removeClass('d-none');
        }else{
            $(`#special_morgen_box`).addClass('d-none');
            $(`#special_mittag_box`).addClass('d-none');
            $(`#special_abend_box`).addClass('d-none');
        }
    }

</script>
@endsection
