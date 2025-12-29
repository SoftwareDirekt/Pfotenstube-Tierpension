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
                            <label for="title">Anrede</label>
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
                            <input type="text" class="form-control" name="id_number" id="id_number" value="{{ old('id_number', $customer->id_number) }}" placeholder="ID-Number" required/>
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
                            <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" placeholder="Telefonnummer" required />
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
@endsection