@extends('admin.layouts.app')
@section('title')
    <title>Neuer Kunde</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
<link rel="stylesheet" href="assets/vendor/libs/bootstrap-select/bootstrap-select.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Neuer Kunde</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.customers.add.post')}}" method="POST" enctype="multipart/form-data" class="custColors">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="type" id="type" class="form-control" required>
                                <option selected value="Stammkunde">Stammkunde</option>
                                <option value="Organisation">Organisation</option>
                            </select>
                            <label for="type">Typ</label>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" name="profession" id="profession" class="form-control" />
                            <label for="profession">Titel</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="title" id="title" class="form-control" required>
                                <option selected value="Mr.">Herr</option>
                                <option value="Mrs.">Frau</option>
                            </select>
                            <label for="title">Anrede</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="name" id="name" placeholder="Vollständiger Name" required />
                            <label for="name">Vollständiger Name</label>
                        </div>
                        @error('name')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="email" id="email" placeholder="Email" />
                            <label for="email">Email</label>
                        </div>
                        @error('email')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="street" id="street" placeholder="Straße" />
                            <label for="street">Straße</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="city" id="city" placeholder="Stadt" />
                            <label for="city">Stadt</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="postcode" id="postcode" placeholder="PLZ" />
                            <label for="postcode">PLZ</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="country" id="country" placeholder="Land" value="Österreich" />
                            <label for="country">Land</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="Telefonnummer" />
                            <label for="phone">Telefonnummer</label>
                        </div>
                        @error('phone')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="emergency_contact" id="emergency_contact" placeholder="Notfallkontakt" />
                            <label for="emergency_contact">Notfallkontakt</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="veterinarian" id="veterinarian" placeholder="Hauseigener Tierarzt"/>
                            <label for="veterinarian">Hauseigener Tierarzt</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="id_number" id="id_number" placeholder="ID-Number"/>
                            <label for="id_number">ID-Number</label>
                        </div>
                        @error('id_number')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="file" class="form-control" name="picture" id="picture" placeholder="Bild"   />
                            <label for="picture">Bild</label>
                        </div>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-md-12">
                        <h5>Kundenhunde</h5>
                    </div>
                </div>
                
                <div class="addDogWrapper" id="addDogWrapper">

                </div>
                <div class="row">
                    <div class="col-md-12 d-flex justify-content-end">
                        <button type="button" class="no-style" style="color: #5a5fe0" onclick="addDogWrapper()">
                            <i class="fa fa-plus"></i>
                            Hund hinzufügen
                        </button>
                    </div>
                </div>
                <hr />
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{route('admin.customers')}}">
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

    var dogs = @json($dogs);
    var counter = 0;

    function deleteDog(key)
    {
        $("#dogItem"+key).remove();
    }
    function addDogWrapper()
    {
        let html = "<div id='dogItem"+counter+"'><div class='row'>";
            html += '<div class="col-md-12 d-flex justify-content-end mb-2"><button type="button" onclick="deleteDog('+counter+')" class="text-danger no-style"><i class="fa fa-times-circle"></i> Hund entfernen</button></div>';
            html += "<div class='col-md-12'>";
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="text" class="form-control" name="dogs['+counter+'][name]" id="name" placeholder="Name" required />';
                    html += '<label for="name">Name</label>';
                html += '</div>';
            html += "</div>";
            html += '<div class="col-md-3">';
                html += '<h6 class="text-gray fw-semibold">Geschlecht:</h6>';
                html += '<div class="d-flex">';
                    html += '<div class="form-check mb-4">';
                        html += '<input type="radio" class="form-check-input" name="dogs['+counter+'][gender]" id="male'+counter+'" checked value="männlich" />';
                        html += '<label for="male'+counter+'">männlich</label>';
                    html += '</div>';
                    html += '<div class="form-check mb-4 mx-5">';
                        html += '<input type="radio" class="form-check-input" name="dogs['+counter+'][gender]" id="female'+counter+'" value="weiblich" />';
                        html += '<label for="female'+counter+'">weiblich</label>';
                    html += '</div>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<h6 class="text-gray fw-semibold">Kompatibilität:</h6>';
                html += '<div class="d-flex">';
                    html += '<div class="form-check mb-4">';
                        html += '<input type="radio" class="form-check-input" name="dogs['+counter+'][compatibility]" id="compatible'+counter+'" value="V" checked />';
                        html += '<label for="compatible'+counter+'">Verträglich</label>';
                    html += '</div>';
                    html += '<div class="form-check mb-4 mx-3">';
                        html += '<input type="radio" class="form-check-input" name="dogs['+counter+'][compatibility]" id="incompatible'+counter+'" value="UV" />';
                        html += '<label for="incompatible'+counter+'">Unverträglich</label>';
                    html += '</div>';
                    html += '<div class="form-check mb-4">';
                        html += '<input type="radio" class="form-check-input" name="dogs['+counter+'][compatibility]" id="compatibleJ'+counter+'" value="VJ" />';
                        html += '<label for="compatibleJ'+counter+'">V mit Jungs</label>';
                    html += '</div>';
                    html += '<div class="form-check mb-4 mx-3">';
                        html += '<input type="radio" class="form-check-input" name="dogs['+counter+'][compatibility]" id="compatibleM'+counter+'" value="VM" />';
                        html += '<label for="compatibleM'+counter+'">V mit Mädls</label>';
                    html += '</div>';
                    html += '<div class="form-check mb-4 mx-3">';
                        html += '<input type="radio" class="form-check-input" name="dogs['+counter+'][compatibility]" id="compatibleS'+counter+'" value="S" />';
                        html += '<label for="compatibleS'+counter+'">Sympatie</label>';
                    html += '</div>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-3">';
                // html += '<h6 class="text-gray fw-semibold">Allergiker:</h6>';
                html += `<div class='d-flex gap-5'>`;
                    html += '<div class="d-flex">';
                    html += '<div class="form-check mb-4">';
                        html += '<input type="checkbox" class="form-check-input" name="dogs['+counter+'][is_allergy]" id="is_allergy'+counter+'" onchange="toggleAllergyInput('+counter+')" />';
                        html += '<label for="is_allergy'+counter+'">Allergiker</label>';
                    html += '</div>';
                html += '</div>';
                html += '<div class="d-flex">';
                    html += '<div class="form-check mb-4">';
                        html += '<input type="checkbox" class="form-check-input" name="dogs['+counter+'][water_lover]" id="water_lover'+counter+'" />';
                        html += '<label for="water_lover'+counter+'">Wassergräber</label>';
                    html += '</div>';
                html += '</div>';
                html += '</div>';
                html += `<div class='mb-2 form-floating form-floating-outline d-none' id='allergyBox${counter}'><input type='text' placeholder='Allergie..' name="dogs[${counter}][allergy]" class='form-control' /><label>Allergie</label></div>`
            html += '</div>';

            html += '<div class="col-md-3">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="file" class="form-control" name="dogs['+counter+'][picture]" id="picture'+counter+'" placeholder="Bild" />';
                    html += '<label for="picture'+counter+'">Bild</label>';
                html += '</div>';
            html += '</div>';
            html += '<div class="col-md-3">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="date" class="form-control" name="dogs['+counter+'][age]" id="age'+counter+'" placeholder="Alter" />';
                    html += '<label for="age'+counter+'">Alter</label>';
                html += '</div>';
            html += '</div>';
            html += '<div class="col-md-3">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<select name="dogs['+counter+'][neutered]" id="neutered'+counter+'" class="form-control">';
                        html += '<option selected value="1">Ja</option>';
                        html += '<option value="0">Nein</option>';
                    html += '</select>';
                    html += '<label for="neutered'+counter+'">Kastriert</label>';
                html += '</div>';
            html += '</div>';
            html += '<div class="col-md-3">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="text" class="form-control" name="dogs['+counter+'][race]" id="race'+counter+'" placeholder="Rasse" />';
                    html += '<label for="race'+counter+'">Rasse</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<select id="dog_friends'+counter+'" name="dogs['+counter+'][dog_friends][]" class="select2 w-100" data-style="btn-default" data-live-search="true" multiple>';
                        dogs.forEach(item => {
                            if(item.customer)
                            {
                                html += `<option data-tokens="ketchup " value="${item.id}">`;
                                    html += `<img src="uploads/users/dogs/${item.picture}" alt="#">`;
                                    html += `${item.name} (${item.customer.name} - ${item.customer.phone})`;
                                html += '</option>';
                            }
                        });
                    html += '</select>';
                    html += '<label for="dog_friends'+counter+'">Hunde Freunde</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="text" class="form-control" name="dogs['+counter+'][chip_number]" id="chip_number" placeholder="Chipnummer"/>';
                    html += '<label for="chip_number">Chipnummer</label>';
                html += '</div>';
            html += '</div>';
        html += "</div>";

        html += '<div class="row">';
            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="text" class="form-control" name="dogs['+counter+'][health_problems]" id="health_problems" placeholder="Gesundheitsprobleme" />';
                    html += '<label for="health_problems">Gesundheitsprobleme</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="number" class="form-control" name="dogs['+counter+'][visits]" id="visits" placeholder="Anzahl an Besuchen" />';
                    html += '<label for="visits">Anzahl an Besuchen</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="number" class="form-control" name="dogs['+counter+'][days]" id="days" placeholder="wie viele Tage" />';
                    html += '<label for="days">wie viele Tage</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<input type="number" class="form-control" name="dogs['+counter+'][weight]" id="weight" placeholder="Gewicht" />';
                    html += '<label for="weight">Gewicht</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<select name="dogs['+counter+'][price_plan]" id="price_plan" class="select2 form-control" required>';
                        html += '<option selected disabled value="">Wählen Sie Preispläne</option>';
                        html += '@foreach($plans as $obj)';
                        html += '<option value="{{$obj->id}}"><?php echo $obj->title; ?></option>';
                        html += '@endforeach';
                    html += '</select>';
                html += '<label for="price_plan">Pensionstarif</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-6">';
                html += '<div class="form-floating form-floating-outline mb-4">';
                    html += '<select name="dogs['+counter+'][daily_rate]" id="daily_rate" class="select2 form-control" required>';
                        html += '<option selected disabled value="">Wählen Sie Preispläne</option>';
                        html += '@foreach($plans as $obj)';
                        html += '<option value="{{$obj->id}}"><?php echo $obj->title; ?></option>';
                        html += '@endforeach';
                    html += '</select>';
                    html += '<label for="daily_rate">Tagestarif</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-12"><div class="row">';
            html += '<div class="col-md-3 my-2">';
                html += '<div class="form-check">';
                    html += '<input type="checkbox" class="form-check-input" name="dogs['+counter+'][is_medication]" id="medication'+counter+'" onchange="toggleMedics('+counter+')"/>';
                    html += '<label for="medication'+counter+'">Medikamente</label>';
                html += '</div>';
                html += '<div class="">';
                    html += '<input type="hidden" class="form-control" name="dogs['+counter+'][medication]" placeholder="Medikamente" />';
                html += '</div>';
            html += '</div>';
            html += '<div class="col-md-3 mb-4 medic_box'+counter+' d-none" id="morgen_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Morgen</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][morgen]" placeholder="Morgen" />';
            html += '</div>';
            html += '<div class="col-md-3 mb-4 medic_box'+counter+' d-none" id="mittag_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Mittag</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][mittag]" placeholder="Mittag" />';
            html += '</div>';
            html += '<div class="col-md-3 mb-4 medic_box'+counter+' d-none" id="abend_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Abend</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][abend]" placeholder="Abend" />';
            html += '</div>';
            html += '</div></div>';

            html += '<div class="col-md-12"><div class="row">';
            html += '<div class="col-md-3 my-2">';
                html += '<div class="form-check">';
                    html += '<input type="checkbox" class="form-check-input" name="dogs['+counter+'][is_eating_habits]" id="is_eating_habits'+counter+'" onchange="toggleEatingHabits('+counter+')" />';
                    html += '<label for="is_eating_habits'+counter+'">Essgewohnheiten</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-3 mb-4 d-none" id="eating_morgen_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Morgen</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][eating_morning]" placeholder="Morgen" />';
            html += '</div>';
            html += '<div class="col-md-3 mb-4 d-none" id="eating_mittag_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Mittag</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][eating_midday]" placeholder="Mittag" />';
            html += '</div>';
            html += '<div class="col-md-3 mb-4 d-none" id="eating_abend_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Abend</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][eating_evening]" placeholder="Abend" />';
            html += '</div>';
            html += '</div></div>';

            html += '<div class="col-md-12"><div class="row">';
            html += '<div class="col-md-3 my-2">';
                html += '<div class="form-check">';
                    html += '<input type="checkbox" class="form-check-input" name="dogs['+counter+'][is_special_eating]" id="is_special_eating'+counter+'" onchange="toggleSpecialFeeding('+counter+')" />';
                    html += '<label for="is_special_eating'+counter+'">Besondere Fütterung</label>';
                html += '</div>';
            html += '</div>';

            html += '<div class="col-md-3 mb-4 d-none" id="special_morgen_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Morgen</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][special_morning]" placeholder="Morgen" />';
            html += '</div>';
            html += '<div class="col-md-3 mb-4 d-none" id="special_mittag_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Mittag</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][special_midday]" placeholder="Mittag" />';
            html += '</div>';
            html += '<div class="col-md-3 mb-4 d-none" id="special_abend_box'+counter+'">';
                html += '<h6 class="text-gray fw-semibold">Abend</h6>';
                html += '<input type="text" class="form-control" name="dogs['+counter+'][special_evening]" placeholder="Abend" />';
            html += '</div>';
            html += '</div></div>';

            html += '<hr />';
            html += '<div class="row abholungdesFrame">';
                html += '<div class="col-md-6">';
                    html += '<h5>Abholung des Hundes</h5>';
                html += '</div>';
                html += '<div class="col-md-6 d-flex justify-content-end">';
                    html += '<button type="button" class="no-style" style="color: #5a5fe0" onclick="addPickup('+counter+')">';
                        html += '<i class="fa fa-plus"></i>';
                        html += 'Weitere hinzufügen';
                    html += '</button>';
                html += '</div>';
            html += '</div>';
            html += '<hr>';
            html += `<div class="addPickupWrapper" id="addPickupWrapper${counter}"></div>`;

            html += '<hr>';
            html += '<div class="row">';
                html += '<div class="col-md-6">';
                    html += '<h5>Impfungen</h5>';
                html += '</div>';
                html += '<div class="col-md-6 d-flex justify-content-end">';
                    html += '<button type="button" class="no-style" style="color: #5a5fe0" onclick="addVaccination('+counter+')">';
                        html += '<i class="fa fa-plus"></i>';
                        html += 'Weitere hinzufügen';
                    html += '</button>';
                html += '</div>';
            html += '</div>';
            html += '<hr>';
            html += `<div class="addVaccinationWrapper" id="addVaccinationWrapper${counter}"></div>`;

            html += '<hr/>';
            html += '<div class="row">';
                html += '<div class="col-md-6">';
                    html += '<h5>Externe Dokumente</h5>';
                html += '</div>';
                html += '<div class="col-md-6 d-flex justify-content-end">';
                    html += '<button type="button" class="no-style" style="color: #5a5fe0" onclick="addDocument('+counter+')">';
                        html += '<i class="fa fa-plus"></i>';
                        html += 'Weitere hinzufügen';
                    html += '</button>';
                html += '</div>';
            html += '</div>';
            html += '<hr/>';
            html += `<div class="addDocumentWrapper" id="addDocumentWrapper${counter}"></div>`;

        html += '</div>';
        html += '</div>';


        $("#addDogWrapper").append(html);
        $("#dog_friends"+counter).select2();
        counter++;
    }

    function addPickup(key)
    {
        var id = $('#addPickupWrapper'+key).children('.item').length;

        let html = "<div id='pickupItem"+id+"' class='item'>";
            html += "<div class='d-flex justify-content-end my-2'><button onclick='removePickup("+id+")' class='no-style text-danger'><i class='fa fa-close'></i> Weitere entfernen</button></div>";
            html += "<div class='row'>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="text" class="form-control" name="dogs['+key+'][picks]['+id+'][name]" id="pick_name'+id+'" placeholder="Name" required/><label for="pick_name'+id+'">Name</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="number" class="form-control" name="dogs['+key+'][picks]['+id+'][phone]" id="pick_phone'+id+'" placeholder="Telefonnummer" required/><label for="pick_phone'+id+'">Telefonnummer</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="text" class="form-control" name="dogs['+key+'][picks]['+id+'][id]" id="pick_id'+id+'" placeholder="ID-Number"/><label for="pick_id'+id+'">ID-Number</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="file" class="form-control" name="dogs['+key+'][picks]['+id+'][file]" id="pick_file'+id+'" placeholder="Bild"/><label for="pick_file'+id+'">Bild</label>';
                html += "</div>";
            html += "</div>";
        html += "</div></div>";

        $("#addPickupWrapper"+key).append(html)
    }

    function removePickup(id)
    {
        $("#pickupItem"+id).remove();
    }

    function addVaccination(key)
    {
        var id = $('#addVaccinationWrapper'+key).children('.item').length;

        let html = "<div id='vaccinationItem"+key+"_"+id+"' class='item'>";
            html += "<div class='d-flex justify-content-end my-2'><button type='button' onclick='removeVaccination("+key+","+id+")' class='no-style text-danger'><i class='fa fa-close'></i> Weitere entfernen</button></div>";
            html += "<div class='row'>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="text" class="form-control" name="dogs['+key+'][vaccinations]['+id+'][vaccine_name]" id="vaccine_name'+key+'_'+id+'" placeholder="Impfstoff Name" required />';
                    html += '<label for="vaccine_name'+key+'_'+id+'">Impfstoff Name</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="date" class="form-control" name="dogs['+key+'][vaccinations]['+id+'][vaccination_date]" id="vaccination_date'+key+'_'+id+'" placeholder="Impfdatum" required />';
                    html += '<label for="vaccination_date'+key+'_'+id+'">Impfdatum</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="date" class="form-control" name="dogs['+key+'][vaccinations]['+id+'][next_vaccination_date]" id="next_vaccination_date'+key+'_'+id+'" placeholder="Nächste Impfung" required />';
                    html += '<label for="next_vaccination_date'+key+'_'+id+'">Nächste Impfung</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-3 mt-3'>";
                html += "<div class='form-check mb-4'>";
                    html += '<input type="hidden" name="dogs['+key+'][vaccinations]['+id+'][is_vaccinated]" value="0" />';
                    html += '<input type="checkbox" class="form-check-input" name="dogs['+key+'][vaccinations]['+id+'][is_vaccinated]" id="is_vaccinated'+key+'_'+id+'" value="1" />';
                    html += '<label for="is_vaccinated'+key+'_'+id+'">Geimpft</label>';
                html += "</div>";
            html += "</div>";
            html += "</div>";
        html += "</div>";

        $("#addVaccinationWrapper"+key).append(html);
    }

    function removeVaccination(key, id)
    {
        $("#vaccinationItem"+key+"_"+id).remove();
    }

    function addDocument(key)
    {
        var id = $('#addDocumentWrapper'+key).children('.item').length;

        let html = "<div id='documentItem"+key+"_"+id+"' class='item'>";
            html += "<div class='d-flex justify-content-end my-2'><button type='button' onclick='removeDocument("+key+","+id+")' class='no-style text-danger'><i class='fa fa-close'></i> Weitere entfernen</button></div>";
            html += "<div class='row'>";
            html += "<div class='col-md-6'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="text" class="form-control" name="dogs['+key+'][documents]['+id+'][name]" id="document_name'+key+'_'+id+'" placeholder="Dokumentname" required />';
                    html += '<label for="document_name'+key+'_'+id+'">Dokumentname</label>';
                html += "</div>";
            html += "</div>";
            html += "<div class='col-md-6'>";
                html += "<div class='form-floating form-floating-outline mb-4'>";
                    html += '<input type="file" class="form-control" name="dogs['+key+'][documents]['+id+'][file]" id="document_file'+key+'_'+id+'" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required />';
                    html += '<label for="document_file'+key+'_'+id+'">Datei (PDF, DOC, DOCX, JPG, PNG)</label>';
                html += "</div>";
            html += "</div>";
            html += "</div>";
        html += "</div>";

        $("#addDocumentWrapper"+key).append(html);
    }

    function removeDocument(key, id)
    {
        $("#documentItem"+key+"_"+id).remove();
    }

    function habitsCheck(i)
    {
        if($("#morgen"+i).is(':checked'))
        {
            $("#morgen_box"+i).removeClass('hidden');
        }else{
            $("#morgen_box"+i+" input").val('');
            $("#morgen_box"+i).addClass('hidden');
        }

        if($("#mittag"+i).is(':checked'))
        {
            $("#mittag_box"+i).removeClass('hidden');
        }else{
            $("#mittag_box"+i+" input").val('');
            $("#mittag_box"+i).addClass('hidden');
        }

        if($("#abend"+i).is(':checked'))
        {
            $("#abend_box"+i).removeClass('hidden');
        }else{
            $("#abend_box"+i+" input").val('');
            $("#abend_box"+i).addClass('hidden');
        }
    }
    function toggleAllergyInput(counter)
    {
        if($(`#is_allergy${counter}`).is(':checked'))
        {
            $(`#allergyBox${counter}`).removeClass('d-none');
        }
        else{
            $(`#allergyBox${counter}`).addClass('d-none');
        }
    }
    function toggleMedics(counter)
    {
        if($(`#medication${counter}`).is(':checked'))
        {
            $(`#morgen_box${counter}`).removeClass('d-none');
            $(`#mittag_box${counter}`).removeClass('d-none');
            $(`#abend_box${counter}`).removeClass('d-none');
        }else{
            $(`#morgen_box${counter}`).addClass('d-none');
            $(`#mittag_box${counter}`).addClass('d-none');
            $(`#abend_box${counter}`).addClass('d-none');
        }
    }
    function toggleEatingHabits(counter)
    {
        if($(`#is_eating_habits${counter}`).is(':checked'))
        {
            $(`#eating_morgen_box${counter}`).removeClass('d-none');
            $(`#eating_mittag_box${counter}`).removeClass('d-none');
            $(`#eating_abend_box${counter}`).removeClass('d-none');
        }else{
            $(`#eating_morgen_box${counter}`).addClass('d-none');
            $(`#eating_mittag_box${counter}`).addClass('d-none');
            $(`#eating_abend_box${counter}`).addClass('d-none');
        }
    }
    function toggleSpecialFeeding(counter)
    {
        if($(`#is_special_eating${counter}`).is(':checked'))
        {
            $(`#special_morgen_box${counter}`).removeClass('d-none');
            $(`#special_mittag_box${counter}`).removeClass('d-none');
            $(`#special_abend_box${counter}`).removeClass('d-none');
        }else{
            $(`#special_morgen_box${counter}`).addClass('d-none');
            $(`#special_mittag_box${counter}`).addClass('d-none');
            $(`#special_abend_box${counter}`).addClass('d-none');
        }
    }
</script>
@endsection
