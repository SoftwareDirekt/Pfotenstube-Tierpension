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
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#profile-tab" aria-controls="profile-tab" aria-selected="true">
                            Admin-Profil
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#preferences-tab" aria-controls="preferences-tab" aria-selected="false">
                            Präferenzen
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile-tab" role="tabpanel">
                        <form action="{{route('admin.settings.post')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                            <div class="row mt-3">
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
                    
                    <!-- Preferences Tab -->
                    <div class="tab-pane fade" id="preferences-tab" role="tabpanel">
                        <form id="preferencesForm" action="{{route('admin.preferences.post')}}" method="POST">
                        @csrf
                            <div class="row mt-3">
                                <div class="card mb-4">
                                    <h5 class="card-header">Mehrwertsteuer (MwSt) Einstellungen</h5>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="vat_percentage" class="form-label">MwSt-Satz (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="vat_percentage" name="vat_percentage" value="{{ \App\Models\Preference::get('vat_percentage', 20) }}" required>
                                                <small class="text-muted">Standard MwSt-Satz für Rechnungen (z.B. 20 für 20%)</small>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mb-0">
                                            <strong>Hinweis:</strong> Alle Preise im System sind ohne Mehrwertsteuer (Netto). Der MwSt-Satz wird nur verwendet, wenn Rechnungen an HelloCash gesendet werden.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
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
