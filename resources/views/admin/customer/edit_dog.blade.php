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

            <hr>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Impfungen</h5>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        <button type="button" class="no-style" style="color: #5a5fe0" data-bs-toggle="modal" data-bs-target="#addVaccinationModal">
                            <i class="fa fa-plus"></i>
                            Impfung hinzufügen
                        </button>
                    </div>
                </div>
                <hr>
                <p id="vaccination_saved" class="text-success" style="display:none;">
                    <i class="far fa-check-circle"></i>
                    Impfung erfolgreich gespeichert
                </p>
                <p id="vaccination_deleted" class="text-success" style="display:none;">
                    <i class="far fa-check-circle"></i>
                    Impfung erfolgreich gelöscht
                </p>
                <p id="vaccination_updated" class="text-success" style="display:none;">
                    <i class="far fa-check-circle"></i>
                    Impfstatus erfolgreich aktualisiert
                </p>
                <div class="table-responsive">
                    <table class="table table-striped" id="vaccinationsTable">
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
                            @if($dog->vaccinations && count($dog->vaccinations) > 0)
                                @foreach($dog->vaccinations as $vaccination)
                                <tr data-id="{{$vaccination->id}}">
                                    <td>{{$vaccination->vaccine_name}}</td>
                                    <td>{{\Carbon\Carbon::parse($vaccination->vaccination_date)->format('d.m.Y')}}</td>
                                    <td>{{\Carbon\Carbon::parse($vaccination->next_vaccination_date)->format('d.m.Y')}}</td>
                                    <td>
                                        <div class="form-check d-flex align-items-center">
                                            <input class="form-check-input vaccination-status" type="checkbox" data-id="{{$vaccination->id}}" {{$vaccination->is_vaccinated ? 'checked' : ''}}>
                                            <label class="form-check-label ms-2">Geimpft</label>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger delete-vaccination" data-id="{{$vaccination->id}}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="5" class="text-center">Keine Impfungen gefunden</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Documents Section --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Externe Dokumente</h5>
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                    <button type="button" class="no-style" style="color: #5a5fe0" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                        <i class="fa fa-plus"></i>
                        Dokument hinzufügen
                    </button>
                </div>
            </div>
            <hr>
            <p id="document_saved" class="text-success" style="display:none;">
                <i class="far fa-check-circle"></i>
                Dokument erfolgreich hochgeladen
            </p>
            <p id="document_deleted" class="text-success" style="display:none;">
                <i class="far fa-check-circle"></i>
                Dokument erfolgreich gelöscht
            </p>
            <div class="table-responsive">
                <table class="table table-striped" id="documentsTable">
                    <thead>
                        <tr>
                            <th>Dokumentname</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($dog->documents && count($dog->documents) > 0)
                            @foreach($dog->documents as $document)
                            <tr data-id="{{$document->id}}">
                                <td>
                                    <a href="{{ asset('uploads/users/documents/' . $document->file_path) }}" target="_blank" style="color: #5a5fe0; text-decoration: none;">
                                        {{$document->name}}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ asset('uploads/users/documents/' . $document->file_path) }}" download class="btn btn-sm btn-info me-1" title="Herunterladen">
                                        <i class="fa fa-download"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger delete-document" data-id="{{$document->id}}" title="Löschen">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="2" class="text-center">Keine Dokumente gefunden</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Document Modal --}}
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-xl modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="mb-4">
            <h3 class="mb-2 pb-1">Dokument hinzufügen</h3>
          </div>
          <form id="documentForm" class="row g-3" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="document_dog_id" name="dog_id" value="{{$dog->id}}">
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="document_name" name="name" placeholder="Dokumentname" required>
                    <label for="document_name">Dokumentname</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="file" class="form-control" id="document_file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                    <label for="document_file">Datei (PDF, DOC, DOCX, JPG, PNG - Max. 10MB)</label>
                </div>
            </div>
            <hr>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
          </form>
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
                    <option
                      data-tokens="ketchup"
                      value="{{$obj->id}}"
                      data-content="<img src='uploads/users/dogs/{{$obj->picture}}' class='me-2' style='width:24px;height:24px;border-radius:50%'> {{ $obj->name }} ({{$obj->customer->name}} - {{$obj->customer->phone }})">
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

{{-- Add Vaccination Modal --}}
<div class="modal fade" id="addVaccinationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-xl modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="mb-4">
            <h3 class="mb-2 pb-1">Impfung hinzufügen</h3>
          </div>
          <form id="vaccinationForm" class="row g-3">
            @csrf
            <input type="hidden" id="vaccination_dog_id" name="dog_id" value="{{$dog->id}}">
            <div class="col-md-4">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="vaccine_name" name="vaccine_name" placeholder="Impfstoff Name" required>
                    <label for="vaccine_name">Impfstoff Name</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="date" class="form-control" id="vaccination_date" name="vaccination_date" placeholder="Impfdatum" required>
                    <label for="vaccination_date">Impfdatum</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="date" class="form-control" id="next_vaccination_date" name="next_vaccination_date" placeholder="Nächste Impfung" required>
                    <label for="next_vaccination_date">Nächste Impfung</label>
                </div>
            </div>
            <hr>
            <div class="col-12 d-flex ">
                <button type="submit" class="btn btn-primary me-sm-3 me-1">
                    Hinzufügen
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
    $('#addFriendsModal').on('shown.bs.modal', function () {
        const $el = $('#dog_friends');
        if ($el.data('selectpicker')) {
            $el.selectpicker('destroy');
        }
        $el.selectpicker();
    });
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
        
        // Show loading state
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Speichern...').prop('disabled', true);
        
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
                    $('#vaccinationForm')[0].reset();
                    $('#addVaccinationModal').modal('hide');
                    $("#vaccination_saved").show();
                    setTimeout(() => {
                        $("#vaccination_saved").hide();
                    }, 3000);
                } else {
                    alert('Fehler: ' + response.message);
                    fetchVaccinations(formData.dog_id);
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
                fetchVaccinations(formData.dog_id);
            },
            complete: function() {
                submitButton.html(originalText).prop('disabled', false);
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
                        $("#vaccination_deleted").show();
                        setTimeout(() => {
                            $("#vaccination_deleted").hide();
                        }, 3000);
                    } else {
                        alert('Fehler: ' + response.message);
                        fetchVaccinations(dogId);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Fehler beim Löschen der Impfung';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    alert(errorMessage);
                    fetchVaccinations(dogId);
                },
                complete: function() {
                    // Reset button state
                    deleteButton.html('<i class="fa fa-trash"></i>').prop('disabled', false);
                }
            });
        }
    });

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
                    fetchVaccinations(dogId);
                    $("#vaccination_updated").show();
                    setTimeout(() => {
                        $("#vaccination_updated").hide();
                    }, 3000);
                } else {
                    alert('Fehler: ' + response.message);
                    checkbox.prop('checked', !isVaccinated);
                    fetchVaccinations(dogId);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Fehler beim Aktualisieren des Impfstatus';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                alert(errorMessage);
                checkbox.prop('checked', !isVaccinated);
                fetchVaccinations(dogId);
            },
            complete: function() {
                // Reset checkbox state
                checkbox.prop('disabled', false);
            }
        });
    });

    // Document AJAX Functions
    function fetchDocuments(dogId) {
        $.ajax({
            url: `/admin/dogs/${dogId}/documents`,
            type: 'GET',
            success: function(documents) {
                displayDocuments(documents);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching documents:', error);
                $("#documentsTable tbody").html('<tr><td colspan="5" class="text-center">Fehler beim Laden der Dokumente</td></tr>');
            }
        });
    }

    function displayDocuments(documents) {
        if (documents && documents.length > 0) {
            var html = "";
            documents.forEach(function(document) {
                html += '<tr data-id="' + document.id + '">';
                html += '<td>';
                html += '<a href="/uploads/users/documents/' + document.file_path + '" target="_blank" style="color: #5a5fe0; text-decoration: none;">' + document.name + '</a>';
                html += '</td>';
                html += '<td>';
                html += '<a href="/uploads/users/documents/' + document.file_path + '" download class="btn btn-sm btn-info me-1" title="Herunterladen"><i class="fa fa-download"></i></a>';
                html += '<button class="btn btn-sm btn-danger delete-document" data-id="' + document.id + '" title="Löschen"><i class="fa fa-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            });
            $("#documentsTable tbody").html(html);
        } else {
            $("#documentsTable tbody").html('<tr><td colspan="2" class="text-center">Keine Dokumente gefunden</td></tr>');
        }
    }

    // Handle document form submission
    $('#documentForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var dogId = $('#document_dog_id').val();
        
        $.ajax({
            url: `/admin/dogs/${dogId}/documents`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#document_saved').fadeIn().delay(3000).fadeOut();
                    $('#addDocumentModal').modal('hide');
                    $('#documentForm')[0].reset();
                    fetchDocuments(dogId);
                } else {
                    alert('Fehler: ' + (response.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr) {
                var errorMessage = 'Ein Fehler ist aufgetreten.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // Handle document deletion
    $(document).on('click', '.delete-document', function() {
        var documentId = $(this).data('id');
        var dogId = $('#document_dog_id').val();
        
        if (!confirm('Möchten Sie dieses Dokument wirklich löschen?')) {
            return;
        }
        
        $.ajax({
            url: `/admin/dogs/documents/${documentId}`,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#document_deleted').fadeIn().delay(3000).fadeOut();
                    fetchDocuments(dogId);
                } else {
                    alert('Fehler: ' + (response.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr) {
                var errorMessage = 'Ein Fehler ist aufgetreten.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

</script>
@endsection
