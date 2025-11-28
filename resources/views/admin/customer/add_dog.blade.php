@extends('admin.layouts.app')
@section('title')
    <title>Neuer Hund</title>
@endsection
@section('extra_css')
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/bootstrap-select/bootstrap-select.css" />
    <style>
        .allergy-input {
            display: none;
        }
    </style>
@endsection
@section('body')
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="row gy-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Neuer Hund</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.customers.add_dog') }}" method="POST" enctype="multipart/form-data"
                        class="custColors">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="customer_id" id="customer_id" class="form-control" required>
                                        @foreach ($customers as $obj)
                                            <option value="{{ $obj->id }}"
                                                {{ $customer_id == $obj->id ? 'selected' : '' }}>
                                                {{ $obj->name }} ({{ $obj->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="customer_id">Kunde</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Name" required />
                                    <label for="name">Name</label>
                                </div>
                                @error('name')
                                    <p class="formError">*{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <h6 class="text-gray fw-semibold">Geschlecht:</h6>
                                <div class="d-flex">
                                    <div class="form-check mb-4">
                                        <input type="radio" class="form-check-input" name="gender" id="male" checked
                                            value="männlich" />
                                        <label for="male">männlich</label>
                                    </div>
                                    <div class="form-check mb-4 mx-3">
                                        <input type="radio" class="form-check-input" name="gender" id="female"
                                            value="weiblich" />
                                        <label for="female">weiblich</label>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="col-md-6 mb-4">
                                <h6 class="text-gray fw-semibold">Kompatibilität:</h6>
                                <div class="d-flex">
                                    <div class="form-check mb-4">
                                        <input type="radio" class="form-check-input" name="compatibility" id="compatible"
                                            value="V" checked />
                                        <label for="compatible">Verträglich</label>
                                    </div>
                                    <div class="form-check mb-4 mx-3">
                                        <input type="radio" class="form-check-input" name="compatibility"
                                            id="incompetible" value="UV" />
                                        <label for="incompetible">Unverträglich</label>
                                    </div>
                                    <div class="form-check mb-4">
                                        <input type="radio" class="form-check-input" name="compatibility" id="compatibleJ"
                                            value="VJ" />
                                        <label for="compatibleJ">V mit Jungs</label>
                                    </div>
                                    <div class="form-check mb-4 mx-3">
                                        <input type="radio" class="form-check-input" name="compatibility" id="compatibleM"
                                            value="VM" />
                                        <label for="compatibleM">V mit Mädls</label>
                                    </div>
                                    <div class="form-check mb-4 mx-3">
                                        <input type="radio" class="form-check-input" name="compatibility"
                                            id="compatibleS" value="S" />
                                        <label for="compatibleS">Sympatie</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                               <div class="d-flex gap-5">
                                    <div class="d-flex">
                                        <div class="form-check mb-4">
                                            <input type="checkbox" class="form-check-input" name="is_allergy" id="is_allergy" onchange="toggleAllergyInput()" />
                                            <label for="is_allergy">Allergiker</label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex">
                                        <div class="form-check mb-4">
                                            <input type="checkbox" class="form-check-input" name="water_lover" id="water_lover"/>
                                            <label for="water_lover">Wassergräber</label>
                                        </div>
                                    </div>
                                </div>
                                <div class='mb-2 form-floating form-floating-outline d-none' id='allergyBox'>
                                    <input type='text' placeholder='Allergie..' name="allergy" class='form-control' />
                                    <label>Allergie</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="file" class="form-control" name="picture" id="picture"
                                        placeholder="Bild" />
                                    <label for="picture">Bild</label>
                                </div>
                                @error('picture')
                                    <p class="formError">*{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="date" class="form-control" name="age" id="age"
                                        placeholder="Alter" />
                                    <label for="age">Alter</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="neutered" id="neutered" class="form-control">
                                        <option selected value="1">Ja</option>
                                        <option value="0">Nein</option>
                                    </select>
                                    <label for="neutered">Kastriert</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" name="race" id="race"
                                        placeholder="Rasse" />
                                    <label for="race">Rasse</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select
                                    id="dog_friends"
                                    name="dog_friends[]"
                                    class="selectpicker w-100"
                                    data-style="btn-default"
                                    data-live-search="true" multiple>
                                    @foreach ($dogs as $obj)
                                        @if($obj->customer)
                                        <option data-tokens="ketchup " value="{{$obj->id}}">
                                            <img src="uploads/users/dogs/{{$obj->picture}}" alt="#">
                                            {{ $obj->name }} ({{$obj->customer->name}} - {{$obj->customer->phone }})
                                        </option>
                                        @endif
                                    @endforeach
                                    </select>
                                    <label for="dog_friends">Hunde Freunde</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" name="chip_number" id="chip_number" placeholder="Chipnummer" />
                                    <label for="chip_number">Chipnummer</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" name="health_problems" id="health_problems" placeholder="Gesundheitsprobleme" />
                                    <label for="health_problems">Gesundheitsprobleme</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" class="form-control" name="visits" id="visits" placeholder="Anzahl an Besuchen"/>
                                    <label for="visits">Anzahl an Besuchen</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" class="form-control" name="days" id="days" placeholder="wie viele Tage"/>
                                    <label for="days">wie viele Tage</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <input type="number" class="form-control" name="weight" id="weight" placeholder="Gewicht"/>
                                    <label for="weight">Gewicht</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="price_plan" id="price_plan" class="select2 form-control" required>
                                        <option selected disabled value="">Wählen Sie Preispläne</option>
                                        @foreach($plans as $obj)
                                        <option value="{{$obj->id}}"><?php echo $obj->title; ?></option>
                                        @endforeach
                                    </select>
                                    <label for="price_plan">Pensionstarif</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <select name="daily_rate" id="daily_rate" class="select2 form-control" required>
                                        <option selected disabled value="">Wählen Sie Preispläne</option>
                                        @foreach($plans as $obj)
                                        <option value="{{$obj->id}}"><?php echo $obj->title; ?></option>
                                        @endforeach
                                    </select>
                                    <label for="daily_rate">Tagestarif</label>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-3 my-2">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="is_medication" id="medication" onchange="toggleMedics()" />
                                            <label for="medication">Medikamente</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="morgen_box">
                                        <h6 class="text-gray fw-semibold">Morgen</h6>
                                        <input type="text" class="form-control" name="morgen" placeholder="Morgen" />
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="mittag_box">
                                        <h6 class="text-gray fw-semibold">Mittag</h6>
                                        <input type="text" class="form-control" name="mittag" placeholder="Mittag" />
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="abend_box">
                                        <h6 class="text-gray fw-semibold">Abend</h6>
                                        <input type="text" class="form-control" name="abend" placeholder="Abend" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-3 my-2">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="is_eating_habits" id="is_eating_habits" onchange="toggleEatingHabits()" />
                                            <label for="is_eating_habits">Essgewohnheiten</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="eating_morgen_box">
                                        <h6 class="text-gray fw-semibold">Morgen</h6>
                                        <input type="text" class="form-control" name="eating_morning" placeholder="Morgen" />
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="eating_mittag_box">
                                        <h6 class="text-gray fw-semibold">Mittag</h6>
                                        <input type="text" class="form-control" name="eating_midday" placeholder="Mittag" />
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="eating_abend_box">
                                        <h6 class="text-gray fw-semibold">Abend</h6>
                                        <input type="text" class="form-control" name="eating_evening" placeholder="Abend" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-3 my-2">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="is_special_eating" id="is_special_eating" onchange="toggleSpecialFeeding()" />
                                            <label for="is_special_eating">Besondere Fütterung</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="special_morgen_box">
                                        <h6 class="text-gray fw-semibold">Morgen</h6>
                                        <input type="text" class="form-control" name="special_morning" placeholder="Morgen" />
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="special_mittag_box">
                                        <h6 class="text-gray fw-semibold">Mittag</h6>
                                        <input type="text" class="form-control" name="special_midday" placeholder="Mittag" />
                                    </div>
                                    <div class="col-md-3 mb-4 d-none" id="special_abend_box">
                                        <h6 class="text-gray fw-semibold">Abend</h6>
                                        <input type="text" class="form-control" name="special_evening" placeholder="Abend" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr/>

                        <div class="row abholungdesFrame">
                            <div class="col-md-6">
                                <h5>Abholung des Hundes</h5>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end">
                                <button type="button" class="no-style" style="color: #5a5fe0" onclick="addPickup()">
                                    <i class="fa fa-plus"></i>
                                    Weitere hinzufügen
                                </button>
                            </div>
                        </div>

                        <hr/>
                        
                        <div class="addPickupWrapper" id="addPickupWrapper">
                            <div id="pickupItem0" class="item">
                            
                            </div>
                        </div>

                        <hr/>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Impfungen</h5>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end">
                                <button type="button" class="no-style" style="color: #5a5fe0" onclick="addVaccination()">
                                    <i class="fa fa-plus"></i>
                                    Weitere hinzufügen
                                </button>
                            </div>
                        </div>

                        <hr/>
                        
                        <div class="addVaccinationWrapper" id="addVaccinationWrapper">
                            <div id="vaccinationItem0" class="item">
                            
                            </div>
                        </div>

                        <hr/>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Externe Dokumente</h5>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end">
                                <button type="button" class="no-style" style="color: #5a5fe0" onclick="addDocument()">
                                    <i class="fa fa-plus"></i>
                                    Weitere hinzufügen
                                </button>
                            </div>
                        </div>

                        <hr/>
                        
                        <div class="addDocumentWrapper" id="addDocumentWrapper">
                            <div id="documentItem0" class="item">
                            
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Speichern</button>
                        <a href="{{ route('admin.customers.preview', ['id' => $customer_id]) }}">
                            <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                        </a>
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
    function toggleAllergyInput(show) {
        const allergyInput = document.getElementById('allergyInput');
        const allergyField = document.getElementById('allergyField');
        allergyInput.style.display = show ? 'block' : 'none';
        if (!isYesSelected) {
            allergyField.value = 'null';
        }
    }
</script>

<script>

    $(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
    });

    function addPickup()
    {
        var id = $('#addPickupWrapper').children('.item').length;

        let html = "<div id='pickupItem"+id+"' class='item'>";
            html += "<div class='d-flex justify-content-end my-2'><button onclick='removePickup("+id+")' class='no-style text-danger'><i class='fa fa-close'></i> Weitere entfernen</button></div>";

            html += "<div class='row'>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-floating form-floating-outline mb-4'>";
            html += '<input type="text" class="form-control" name="pick_name[]" id="pick_name' + id +
                '" placeholder="Name" required/><label for="pick_name' + id + '">Name</label>';
            html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-floating form-floating-outline mb-4'>";
            html += '<input type="number" class="form-control" name="pick_phone[]" id="pick_phone' + id +
                '" placeholder="Telefonnummer" required/><label for="pick_phone' + id + '">Telefonnummer</label>';
            html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-floating form-floating-outline mb-4'>";
            html += '<input type="text" class="form-control" name="pick_id[]" id="pick_id' + id +
                '" placeholder="ID-Number"/><label for="pick_id' + id + '">ID-Number</label>';
            html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-floating form-floating-outline mb-4'>";
            html += '<input type="file" class="form-control" name="pick_file[]" id="pick_file' + id +
                '" placeholder="Bild"/><label for="pick_file' + id + '">Bild</label>';
            html += "</div>";
        html += "</div></div>";

        $("#addPickupWrapper").append(html)
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
    function removePickup(id) {
    $("#pickupItem" + id).remove();
    }

    function addVaccination()
    {
        var id = $('#addVaccinationWrapper').children('.item').length;

        let html = "<div id='vaccinationItem"+id+"' class='item'>";
            html += "<div class='d-flex justify-content-end my-2'><button type='button' onclick='removeVaccination("+id+")' class='no-style text-danger'><i class='fa fa-close'></i> Weitere entfernen</button></div>";

            html += "<div class='row'>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-floating form-floating-outline mb-4'>";
            html += '<input type="text" class="form-control" name="vaccine_name[]" id="vaccine_name' + id +
                '" placeholder="Impfstoff Name" required/><label for="vaccine_name' + id + '">Impfstoff Name</label>';
            html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-floating form-floating-outline mb-4'>";
            html += '<input type="date" class="form-control" name="vaccination_date[]" id="vaccination_date' + id +
                '" placeholder="Impfdatum" required/><label for="vaccination_date' + id + '">Impfdatum</label>';
            html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-floating form-floating-outline mb-4'>";
            html += '<input type="date" class="form-control" name="next_vaccination_date[]" id="next_vaccination_date' + id +
                '" placeholder="Nächste Impfung" required/><label for="next_vaccination_date' + id + '">Nächste Impfung</label>';
            html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
            html += "<div class='form-check mb-4 mt-3'>";
            html += '<input type="hidden" name="is_vaccinated[' + id + ']" value="0"/>';
            html += '<input type="checkbox" class="form-check-input" name="is_vaccinated[' + id + ']" id="is_vaccinated' + id +
                '" value="1"/><label for="is_vaccinated' + id + '">Geimpft</label>';
            html += "</div>";
            html += "</div>";
        html += "</div></div>";

        $("#addVaccinationWrapper").append(html)
    }

    function removeVaccination(id) {
        $("#vaccinationItem" + id).remove();
    }

    function addDocument()
    {
        var id = $('#addDocumentWrapper').children('.item').length;

        let html = "<div id='documentItem"+id+"' class='item'>";
            html += "<div class='d-flex justify-content-end my-2'><button type='button' onclick='removeDocument("+id+")' class='no-style text-danger'><i class='fa fa-close'></i> Weitere entfernen</button></div>";
            html += "<div class='row'>";
            html += "<div class='col-md-6'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="text" class="form-control" name="document_name[]" id="document_name' + id + '" placeholder="Dokumentname" required/><label for="document_name' + id + '">Dokumentname</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-6'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="file" class="form-control" name="document_file[]" id="document_file' + id + '" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required/><label for="document_file' + id + '">Datei (PDF, DOC, DOCX, JPG, PNG)</label>';
                html += "</div>";
            html += "</div>";
            html += "</div>";
        html += "</div>";

        $("#addDocumentWrapper").append(html);
    }

    function removeDocument(id) {
        $("#documentItem" + id).remove();
    }

</script>

@endsection
