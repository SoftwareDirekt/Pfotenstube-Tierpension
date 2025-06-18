@extends('admin.layouts.app')
@section('title')
    <title>Einstellungen</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-2">Admin-Profil</h5>
            </div>
            <div class="card-body">
                <form action="{{route('admin.settings.post')}}" method="POST" enctype="multipart/form-data">
                @csrf
                    <div class="row">
                        <div class="card mb-4">
                            <h5 class="card-header">Kennwort ändern</h5>
                            <div class="card-body">
                            <form id="formAccountSettings" method="POST" action="">
                                <div class="row">
                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <div class="input-group input-group-merge">
                                        <div class="form-floating form-floating-outline">
                                            <input class="form-control" type="password" name="currentPassword" id="currentPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                            <label for="currentPassword">Aktuelles Passwort</label>
                                        </div>
                                        <span class="input-group-text cursor-pointer">
                                            <i class="mdi mdi-eye-off-outline"></i>
                                        </span>
                                    </div>
                                    @error('currentPassword')
                                        <p class="formError">*{{$message}}</p>
                                    @enderror
                                </div>
                                </div>
                                <div class="row g-3 mb-4">
                                <div class="col-md-6 form-password-toggle">
                                    <div class="input-group input-group-merge">
                                        <div class="form-floating form-floating-outline">
                                            <input class="form-control" type="password" id="newPassword" name="newPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                            <label for="newPassword">Neues Kennwort</label>
                                        </div>
                                        <span class="input-group-text cursor-pointer">
                                            <i class="mdi mdi-eye-off-outline"></i>
                                        </span>
                                    </div>
                                    @error('newPassword')
                                        <p class="formError">*{{$message}}</p>
                                    @enderror
                                </div>
                                <div class="col-md-6 form-password-toggle">
                                    <div class="input-group input-group-merge">
                                        <div class="form-floating form-floating-outline">
                                            <input class="form-control" type="password" name="confirmPassword" id="confirmPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                            <label for="confirmPassword">Bestätige Passwort</label>
                                        </div>
                                        <span class="input-group-text cursor-pointer">
                                            <i class="mdi mdi-eye-off-outline"></i>
                                        </span>
                                    </div>
                                    @error('confirmPassword')
                                        <p class="formError">*{{$message}}</p>
                                    @enderror
                                </div>
                                </div>
                                <h6 class="text-body">Passwort Anforderungen:</h6>
                                <ul class="ps-3 mb-0">
                                <li class="mb-1">Mindestens 8 Zeichen lang - je mehr, desto besser</li>
                                <li class="mb-1">Mindestens ein Großbuchstabe</li>
                                <li>Mindestens eine Zahl, ein Symbol oder ein Leerzeichen</li>
                                </ul>
                                <div class="mt-4">
                                <button type="submit" class="btn btn-primary me-2">Änderungen Speichern</button>
                                <a href="{{(route('admin.dashboard'))}}">
                                    <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                                </a>
                                </div>
                            </form>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('extra_js')
<script src="assets/vendor/libs/select2/select2.js"></script>
<script>
   
</script>
@endsection