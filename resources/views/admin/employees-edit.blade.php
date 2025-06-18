@extends('admin.layouts.app')
@section('title')
    <title>Mitarbeiter aktualisieren</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Mitarbeiter aktualisieren</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.employees.update')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="name" id="name" value="{{$user->name}}" placeholder="Name" required />
                            <label for="name">Name</label>
                        </div>
                        @error('name')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="department" id="abteilung" value="{{$user->department}}" placeholder="Abteilung" />
                            <label for="abteilung">Abteilung</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" id="email" value="{{$user->email}}" placeholder="Email" disabled/>
                            <label for="email">Email</label>
                        </div>
                        @error('email')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="password" id="passwort" placeholder="********" />
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
                            <input type="text" class="form-control" name="address" id="adresse" value="{{$user->address}}" placeholder="Adresse" />
                            <label for="adresse">Adresse</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="city" id="city" value="{{$user->city}}" placeholder="Stadt" />
                            <label for="city">Stadt</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="country" id="country" value="{{$user->country}}" placeholder="Land" />
                            <label for="country">Land</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="number" class="form-control" name="phone" id="phone" value="{{$user->phone}}" placeholder="Tel. Nummer" />
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
                            @php
                            $permissions = isset($user->permissions) && !empty($user->permissions) ? json_decode($user->permissions,true) : [];
                            @endphp
                            <select id="multicol-language" class="select2 form-select" name="permissions[]" multiple>
                                @if(isset($pages))
                                    @foreach($pages as $page)
                                        <option value="{{$page->id}}" {{in_array($page->id,$permissions) ? 'selected':''}}>{{$page->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <label for="roles">Seitenerlaubnis</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        @if ($user->picture)
                            <img src="uploads/users/{{$user->picture}}" width="100" height="100" style="object-fit: cover" alt="#">
                        @endif
                    </div>
                </div>
                <input type="hidden" name="id" value="{{$user->id}}">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{route('admin.employees')}}">
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
