@extends('admin.layouts.app')
@section('title')
    <title>Neuer Mitarbeiter</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Neuer Mitarbeiter</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.employees.add.post')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="name" id="name" placeholder="Name" required />
                            <label for="name">Name</label>
                        </div>
                        @error('name')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="department" id="abteilung" placeholder="Abteilung" />
                            <label for="abteilung">Abteilung</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="email" id="email" placeholder="Email" required />
                            <label for="email">Email</label>
                        </div>
                        @error('email')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="password" id="passwort" placeholder="Passwort" required />
                            <label for="passwort">Passwort</label>
                        </div>
                        @error('password')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="address" id="adresse" placeholder="Adresse" />
                            <label for="adresse">Adresse</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="city" id="city" placeholder="Stadt" />
                            <label for="city">Stadt</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="country" id="country" placeholder="Land" />
                            <label for="country">Land</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="number" class="form-control" name="phone" id="phone" placeholder="Tel. Nummer" />
                            <label for="phone">Tel. Nummer</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="file" class="form-control" name="picture" id="picture" placeholder="Bild" />
                            <label for="picture">Bild</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select id="multicol-language" class="select2 form-select" data-placeholder="Seitenerlaubnis" name="permissions[]" multiple>
                                @if(isset($pages))
                                    @foreach($pages as $page)
                                        <option value="{{$page->id}}">{{$page->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <label for="roles">Seitenerlaubnis</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{route('admin.employees')}}">'
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
