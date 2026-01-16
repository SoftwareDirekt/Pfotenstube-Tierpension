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
              <h5 class="mb-2">Einstellungen</h5>
            </div>
            <div class="card-body">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#basic-info-tab" aria-controls="basic-info-tab" aria-selected="true">
                            <i class="mdi mdi-account-outline me-1"></i>
                            Profilinformationen
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#profile-tab" aria-controls="profile-tab" aria-selected="false">
                            <i class="mdi mdi-lock-outline me-1"></i>
                            Passwort
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#preferences-tab" aria-controls="preferences-tab" aria-selected="false">
                            <i class="mdi mdi-percent me-1"></i>
                            MwSt
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Profile Information Tab -->
                    <div class="tab-pane fade show active" id="basic-info-tab" role="tabpanel">
                        <form id="basicInfoForm" action="{{route('admin.basic-info.post')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                            <div class="row mt-3">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="company_name" class="form-label">Firmenname <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name', $user->company_name ?? '') }}" required>
                                                @error('company_name')
                                                    <p class="formError">*{{$message}}</p>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="company_email" class="form-label">Firmen-E-Mail <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="company_email" name="company_email" value="{{ old('company_email', $user->company_email ?? '') }}" required>
                                                @error('company_email')
                                                    <p class="formError">*{{$message}}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="address" class="form-label">Adresse</label>
                                                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $user->address ?? '') }}">
                                                @error('address')
                                                    <p class="formError">*{{$message}}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="city" class="form-label">Stadt</label>
                                                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $user->city ?? '') }}">
                                                @error('city')
                                                    <p class="formError">*{{$message}}</p>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="country" class="form-label">Land</label>
                                                <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $user->country ?? '') }}">
                                                @error('country')
                                                    <p class="formError">*{{$message}}</p>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="phone" class="form-label">Telefonnummer</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
                                                @error('phone')
                                                    <p class="formError">*{{$message}}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="picture" class="form-label">Bild <span class="text-primary">(Erlaubte Formate: JPEG, PNG, JPG, GIF. Max. Größe: 2MB)</span></label>
                                                <input type="file" class="form-control" id="picture" name="picture" accept="image/*">
                                                @error('picture')
                                                    <p class="formError">*{{$message}}</p>
                                                @enderror
                                                @if($user->picture && $user->picture != 'no-user-picture.gif')
                                                    <div class="mt-2">
                                                        <img src="{{ asset('uploads/users/' . $user->picture) }}" alt="Current Picture" style="max-width: 150px; max-height: 150px; border-radius: 5px;">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary me-2">Grundinformationen Speichern</button>
                                <a href="{{(route('admin.dashboard'))}}">
                                    <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="profile-tab" role="tabpanel">
                        <form action="{{route('admin.settings.post')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                            <div class="row mt-3">
                                <div class="card mb-4">
                                    <div class="card-body">
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
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary me-2">Änderungen Speichern</button>
                                <a href="{{(route('admin.dashboard'))}}">
                                    <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Preferences Tab -->
                    <div class="tab-pane fade" id="preferences-tab" role="tabpanel">
                        <form id="preferencesForm" action="{{route('admin.preferences.post')}}" method="POST">
                        @csrf
                            <div class="row mt-3">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="vat_percentage" class="form-label">MwSt-Satz (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="vat_percentage" name="vat_percentage" value="{{ \App\Models\Preference::get('vat_percentage', 20) }}" required>
                                                <small class="text-muted">Standard MwSt-Satz für Rechnungen (z.B. 20 für 20%)</small>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mb-0">
                                            <strong>Hinweis:</strong> Alle Preise im System sind ohne Mehrwertsteuer (Netto). Der MwSt-Satz wird nur verwendet, wenn Rechnungen an die Registrierkasse gesendet werden.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary me-2">Präferenzen Speichern</button>
                                <a href="{{(route('admin.dashboard'))}}">
                                    <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('extra_js')
<script src="assets/vendor/libs/select2/select2.js"></script>
<script>
    // Handle basic info form submission
    $('#basicInfoForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Update header user pictures if picture was uploaded
                    if (response.user && response.user.picture) {
                        var pictureUrl = 'uploads/users/' + response.user.picture;
                        $('#headerUserAvatar').attr('src', pictureUrl);
                        $('#headerUserAvatarDropdown').attr('src', pictureUrl);
                        
                        // Update sidebar user picture if it exists
                        var sidebarPicture = $('#sidebarUserPicture img');
                        if (sidebarPicture.length > 0) {
                            sidebarPicture.attr('src', pictureUrl);
                        } else {
                            // Create sidebar picture if it doesn't exist
                            var sidebarHtml = '<div class="app-brand-logo demo me-2" id="sidebarUserPicture">' +
                                '<img src="' + pictureUrl + '" alt="User" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" />' +
                                '</div>';
                            $('.app-brand-link').prepend(sidebarHtml);
                        }
                    }
                    
                    // Show success message
                    alert('Grundinformationen erfolgreich gespeichert!');
                    // Reload the page to show updated info
                    window.location.reload();
                } else {
                    alert('Fehler beim Speichern: ' + (response.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr) {
                var errorMsg = 'Fehler beim Speichern der Grundinformationen';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    });
    
    // Handle preferences form submission
    $('#preferencesForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert('Präferenzen erfolgreich gespeichert!');
                    // Optionally reload the page
                    window.location.reload();
                } else {
                    alert('Fehler beim Speichern: ' + (response.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr) {
                var errorMsg = 'Fehler beim Speichern der Präferenzen';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    });
</script>
@endsection
