@extends('admin.layouts.app')
@section('title')
    <title>Kunde aktualisieren</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Kunde aktualisieren</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.customers.update')}}" method="POST" enctype="multipart/form-data" class="custColors">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="type" id="type" class="form-control" required>
                                <option value="Stammkunde" {{ old('type', $customer->type) == 'Stammkunde' ? 'selected' : '' }}>Stammkunde</option>
                                <option value="Organisation" {{ old('type', $customer->type) == 'Organisation' ? 'selected' : '' }}>Organisation</option>
                            </select>
                            <label for="type">Typ <span class="text-danger">*</span></label>
                            @error('type')
                                <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="title" id="title" class="form-control" required>
                                <option value="Mr." {{ old('title', $customer->title) == 'Mr.' ? 'selected' : '' }}>Herr</option>
                                <option value="Mrs." {{ old('title', $customer->title) == 'Mrs.' ? 'selected' : '' }}>Frau</option>
                            </select>
                            <label for="title">Anrede <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $customer->name) }}" placeholder="Vollständiger Name" required />
                            <label for="name">Vollständiger Name <span class="text-danger">*</span></label>
                            @error('name')
                                <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="id_number" id="id_number" value="{{ old('id_number', $customer->id_number) }}" placeholder="ID-Number"/>
                            <label for="id_number">ID-Number</label>
                            @error('id_number')
                                <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" name="profession" id="profession" value="{{ old('profession', $customer->profession) }}" class="form-control" />
                            <label for="profession">Organisation / Beruf</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="email" class="form-control" name="email" id="email" value="{{ old('email', $customer->email) }}" placeholder="Email" />
                            <label for="email">Email</label>
                            @error('email')
                                <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" placeholder="Telefonnummer" />
                            <label for="phone">Telefonnummer</label>
                            @error('phone')
                                <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="emergency_contact" id="emergency_contact" value="{{ old('emergency_contact', $customer->emergency_contact) }}" placeholder="Notfallkontakt" />
                            <label for="emergency_contact">Notfallkontakt</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="veterinarian" id="veterinarian" value="{{ old('veterinarian', $customer->veterinarian) }}" placeholder="Hauseigener Tierarzt"/>
                            <label for="veterinarian">Hauseigener Tierarzt</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="street" id="street" value="{{ old('street', $customer->street) }}" placeholder="Straße" />
                            <label for="street">Straße & Hausnummer</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="city" id="city" value="{{ old('city', $customer->city) }}" placeholder="Stadt" />
                            <label for="city">Stadt</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="postcode" id="postcode" value="{{ old('postcode', $customer->zipcode) }}" placeholder="PLZ" />
                            <label for="postcode">PLZ</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="country" id="country" value="{{ old('country', $customer->country) }}" placeholder="Land" />
                            <label for="country">Land</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="file" class="form-control" name="picture" id="picture" placeholder="Bild" />
                            <label for="picture">Bild</label>
                        </div>
                        @if($customer->picture != null)
                            <div class="mb-3">
                                <img src="uploads/users/{{$customer->picture}}" width="100" height="100" alt="Profilbild" class="rounded" style="object-fit: cover;">
                            </div>
                        @endif
                    </div>
                </div>
                <input type="hidden" class="form-control" name="id" value="{{$customer->id}}">
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
<script src="assets/vendor/libs/select2/select2.js"></script>
<script>
    const customerId = {{ $customer->id }};

    // Real-time email validation
    let emailCheckTimeout;
    const emailInput = document.getElementById('email');
    const emailContainer = emailInput.closest('.col-md-6');
    
    emailInput.addEventListener('input', function() {
        clearTimeout(emailCheckTimeout);
        
        const email = this.value.trim();
        
        // Remove any existing error message
        const existingError = emailContainer.querySelector('.email-validation-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Only check if email is not empty and looks valid
        if (email.length > 0 && email.includes('@')) {
            emailCheckTimeout = setTimeout(() => {
                checkEmailAvailability(email);
            }, 500);
        } else if (email.length === 0) {
            updateSubmitButton();
        }
    });
    
    function checkEmailAvailability(email) {
        fetch('{{ route("admin.customers.check.email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email, exclude_id: customerId })
        })
        .then(response => response.json())
        .then(data => {
            const existingError = emailContainer.querySelector('.email-validation-error');
            if (existingError) {
                existingError.remove();
            }
            
            if (data.exists) {
                const errorMsg = document.createElement('p');
                errorMsg.className = 'formError email-validation-error text-danger';
                errorMsg.textContent = '*' + data.message;
                emailContainer.appendChild(errorMsg);
                
                emailInput.classList.add('is-invalid');
                emailInput.dataset.isValid = 'false';
            } else {
                emailInput.classList.remove('is-invalid');
                emailInput.dataset.isValid = 'true';
            }
            
            updateSubmitButton();
        })
        .catch(error => {
            console.error('Error checking email:', error);
        });
    }

    // Real-time ID number validation
    let idNumberCheckTimeout;
    const idNumberInput = document.getElementById('id_number');
    const idNumberContainer = idNumberInput.closest('.col-md-6');
    
    idNumberInput.addEventListener('input', function() {
        clearTimeout(idNumberCheckTimeout);
        
        const idNumber = this.value.trim();
        
        const existingError = idNumberContainer.querySelector('.id-validation-error');
        if (existingError) {
            existingError.remove();
        }
        
        if (idNumber.length > 0) {
            idNumberCheckTimeout = setTimeout(() => {
                checkIdNumberAvailability(idNumber);
            }, 500);
        } else {
            updateSubmitButton();
        }
    });
    
    function checkIdNumberAvailability(idNumber) {
        fetch('{{ route("admin.customers.check.id-number") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ id_number: idNumber, exclude_id: customerId })
        })
        .then(response => response.json())
        .then(data => {
            const existingError = idNumberContainer.querySelector('.id-validation-error');
            if (existingError) {
                existingError.remove();
            }
            
            if (data.exists) {
                const errorMsg = document.createElement('p');
                errorMsg.className = 'formError id-validation-error text-danger';
                errorMsg.textContent = '*' + data.message;
                idNumberContainer.appendChild(errorMsg);
                
                idNumberInput.classList.add('is-invalid');
                idNumberInput.dataset.isValid = 'false';
            } else {
                idNumberInput.classList.remove('is-invalid');
                idNumberInput.dataset.isValid = 'true';
            }
            
            updateSubmitButton();
        })
        .catch(error => {
            console.error('Error checking ID number:', error);
        });
    }

    function updateSubmitButton() {
        const submitBtn = document.querySelector('button[type="submit"]');
        const emailValid = emailInput.dataset.isValid !== 'false';
        const idNumberValid = idNumberInput.dataset.isValid !== 'false';
        
        submitBtn.disabled = !emailValid || !idNumberValid;
    }
</script>
@endsection