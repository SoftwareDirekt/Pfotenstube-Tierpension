@extends('admin.layouts.app')
@section('title')
    <title>Pflegevereinbarung – {{ $reservation->dog->name ?? 'Reservierung' }}</title>
@endsection
@section('body')
@php
    use App\Models\BoardingCareAgreement;
    $care = $agreement->care_options ?? BoardingCareAgreement::defaultCareOptions();
    $f = $care['futter'] ?? [];
    $bad = $care['bad'] ?? [];
    $medFormRows = BoardingCareAgreement::medikamenteRowsForForm($care, old('med_items'));
@endphp
<div class="px-4 flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0">Pflegevereinbarung</h5>
                <small class="text-muted">{{ $reservation->dog->name ?? '' }} · Reservierung #{{ $reservation->id }}</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.reservation') }}">Zur Reservierungsliste</a>
                @if($agreement->final_pdf_path)
                    <a class="btn btn-sm btn-primary" href="{{ route('admin.reservation.pflegevertrag.download', $reservation->id) }}" target="_blank">PDF herunterladen</a>
                @endif
                @if($agreement->status === BoardingCareAgreement::STATUS_COMPLETED)
                    <form method="post" action="{{ route('admin.reservation.pflegevertrag.email', $reservation->id) }}" class="d-inline" id="email-send-form">
                        @csrf
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-open-email-confirm" @if(!($reservation->dog->customer->email ?? null)) disabled title="Keine E-Mail beim Kunden"@endif>
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            Per E-Mail an Kunden senden
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @php
                $statusLabel = match ($agreement->status) {
                    BoardingCareAgreement::STATUS_DRAFT => 'Entwurf – Formular & Abgabe-Unterschrift offen',
                    BoardingCareAgreement::STATUS_INTAKE_SIGNED => 'Abgabe unterschrieben – Abholung offen',
                    BoardingCareAgreement::STATUS_COMPLETED => 'Abgeschlossen',
                    default => $agreement->status,
                };
            @endphp
            <p class="mb-4"><span class="badge bg-label-primary">{{ $statusLabel }}</span>
                @if($agreement->intake_signed_at)
                    <span class="text-muted small ms-2">Abgabe: {{ $agreement->intake_signed_at->format('d.m.Y') }}</span>
                @endif
                @if($agreement->checkout_signed_at)
                    <span class="text-muted small ms-2">Abholung: {{ $agreement->checkout_signed_at->format('d.m.Y') }}</span>
                @endif
                @if($agreement->email_sent_at)
                    <span class="text-muted small ms-2">E-Mail: {{ $agreement->email_sent_at->format('d.m.Y') }}</span>
                @endif
            </p>

            <form id="pflegevertrag-form" method="post" action="{{ route('admin.reservation.pflegevertrag.save', $reservation->id) }}">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-semibold">§2 Besonderheiten</label>
                    <textarea class="form-control @error('besonderheiten') is-invalid @enderror" name="besonderheiten" rows="5"
                        @unless($agreement->canEditForm()) readonly disabled @endunless>{{ old('besonderheiten', $agreement->besonderheiten) }}</textarea>
                    @error('besonderheiten')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-2 fw-semibold">§4 Futter (Häufigkeit optional)</div>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th>Art</th><th>Häufigkeit pro Tag</th></tr></thead>
                        <tbody>
                        @foreach ([
                            'dosenfutter' => 'Dosenfutter',
                            'trockenfutter' => 'Trockenfutter',
                            'fleisch' => 'Fleisch',
                            'diaet' => 'Diät',
                        ] as $key => $label)
                            @php $row = $f[$key] ?? ['on' => false, 'freq' => null]; @endphp
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    <select class="form-select form-select-sm" name="futter_{{ $key }}_freq"
                                        @unless($agreement->canEditForm()) disabled @endunless>
                                        <option value="">—</option>
                                        <option value="1" @selected((int)old("futter_{$key}_freq", $row['freq'] ?? 0) === 1)>1 Mal/T.</option>
                                        <option value="2" @selected((int)old("futter_{$key}_freq", $row['freq'] ?? 0) === 2)>2 Mal/T.</option>
                                        <option value="3" @selected((int)old("futter_{$key}_freq", $row['freq'] ?? 0) === 3)>3 Mal/T.</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $badChoice = old('bad_choice');
                    if ($badChoice === null) {
                        if (!empty($bad['bei_abholung'])) {
                            $badChoice = 'bei_abholung';
                        } elseif (!empty($bad['einmal_woche'])) {
                            $badChoice = 'einmal_woche';
                        } elseif (!empty($bad['schur'])) {
                            $badChoice = 'schur';
                        } else {
                            $badChoice = '';
                        }
                    }
                @endphp
                <div class="mb-2 fw-semibold">Bad</div>
                <div class="mb-4">
                    <select class="form-select form-select-sm" name="bad_choice" id="bad_choice"
                        @unless($agreement->canEditForm()) disabled @endunless>
                        <option value="">—</option>
                        <option value="bei_abholung" @selected($badChoice === 'bei_abholung')>Bei Abholung</option>
                        <option value="einmal_woche" @selected($badChoice === 'einmal_woche')>1 Mal / Woche</option>
                        <option value="schur" @selected($badChoice === 'schur')>Schur</option>
                    </select>
                </div>

                <div class="mb-2 fw-semibold">Medikamente (Notiz und/oder Häufigkeit)</div>
                <p class="small text-muted mb-2">Für jedes Präparat eine Zeile; bei Bedarf weitere Zeilen hinzufügen.</p>
                <div id="med-item-rows" class="mb-2">
                    @foreach($medFormRows as $idx => $mrow)
                        <div class="row g-2 mb-2 align-items-end med-item-row">
                            <div class="col-md-6 col-lg-5">
                                <label class="form-label small">Notiz</label>
                                <input type="text" class="form-control form-control-sm" name="med_items[{{ $idx }}][note]" value="{{ $mrow['note'] }}"
                                    @unless($agreement->canEditForm()) readonly disabled @endunless>
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small">Häufigkeit</label>
                                <select class="form-select form-select-sm" name="med_items[{{ $idx }}][freq]"
                                    @unless($agreement->canEditForm()) disabled @endunless>
                                    <option value="">—</option>
                                    <option value="1" @selected((string)($mrow['freq'] ?? '') === '1')>1 Mal/T.</option>
                                    <option value="2" @selected((string)($mrow['freq'] ?? '') === '2')>2 Mal/T.</option>
                                    <option value="3" @selected((string)($mrow['freq'] ?? '') === '3')>3 Mal/T.</option>
                                </select>
                            </div>
                            @if($agreement->canEditForm())
                                <div class="col-md-2 col-lg-1 med-remove-col @if($loop->count < 2) d-none @endif">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 med-item-remove" title="Zeile entfernen" aria-label="Zeile entfernen">&times;</button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($agreement->canEditForm())
                    <div class="mb-4">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="med-item-add">+ Weitere Medikation</button>
                    </div>
                @endif

                @if($agreement->canEditForm())
                    <button type="submit" class="btn btn-primary js-action-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Speichern
                    </button>
                @endif
            </form>

            <hr class="my-4">

            <div class="d-flex flex-wrap gap-2 mb-4">
                <form method="post" action="{{ route('admin.reservation.pflegevertrag.preview', $reservation->id) }}" target="_blank" id="preview-form" class="d-inline">
                    @csrf
                    <input type="hidden" name="besonderheiten" id="preview-besonderheiten" value="">
                </form>
                <button type="button" class="btn btn-outline-primary btn-sm js-action-btn" id="btn-preview-pdf">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    PDF-Vorschau (Formular wie angezeigt)
                </button>
                <span class="text-muted small align-self-center">Öffnet in neuem Tab; speichert nicht automatisch.</span>
            </div>

            @if($agreement->canSignIntake())
                <div class="border rounded p-3 mb-4">
                    <h6 class="mb-2">Unterschrift bei Abgabe (Halter)</h6>
                    <p class="small text-muted">Bitte Formular speichern oder hier mitzeichnen – beim Speichern der Unterschrift werden die aktuellen Formularwerte mitübernommen.</p>
                    <canvas id="canvas-intake" class="border bg-white rounded" width="500" height="180" style="max-width:100%;touch-action:none;"></canvas>
                    <div class="mt-2 d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-intake">Leeren</button>
                        <form method="post" action="{{ route('admin.reservation.pflegevertrag.sign.intake', $reservation->id) }}" id="form-sign-intake">
                        @csrf
                            <input type="hidden" name="signature" id="input-signature-intake">
                            <input type="hidden" name="besonderheiten" id="sign-intake-besonderheiten">
                            @foreach ([ 'dosenfutter','trockenfutter','fleisch','diaet' ] as $k)
                                <input type="hidden" name="futter_{{ $k }}_freq" value="" class="mirror-futter-freq-{{ $k }}">
                            @endforeach
                            <input type="hidden" name="bad_choice" value="" class="mirror-bad-choice">
                            <div id="intake-med-mirror" class="d-none" aria-hidden="true"></div>
                            <button type="submit" class="btn btn-success btn-sm js-action-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                Unterschrift speichern
                            </button>
                        </form>
                    </div>
                    
                </div>
            @endif

            @if($agreement->canSignCheckout())
                <div class="border rounded p-3 mb-4">
                    <h6 class="mb-2">Unterschrift bei Abholung</h6>
                    <canvas id="canvas-checkout" class="border bg-white rounded" width="500" height="180" style="max-width:100%;touch-action:none;"></canvas>
                    <div class="mt-2 d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-checkout">Leeren</button>
                        <form method="post" action="{{ route('admin.reservation.pflegevertrag.sign.checkout', $reservation->id) }}" id="form-sign-checkout">
                            @csrf
                            <input type="hidden" name="signature" id="input-signature-checkout">
                            <button type="submit" class="btn btn-success btn-sm js-action-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                Abholung abschließen &amp; PDF erzeugen
                            </button>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

@if($agreement->status === BoardingCareAgreement::STATUS_COMPLETED)
<div class="modal fade" id="emailConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pflegevereinbarung senden</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                Pflegevereinbarung an
                <strong>{{ $reservation->dog->customer->email ?? 'Kunden-E-Mail' }}</strong>
                senden?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary js-action-btn" id="btn-confirm-email-send">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    Senden
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
@section('extra_js')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    function syncPreviewHidden() {
        var form = document.getElementById('pflegevertrag-form');
        if (!form) return;
        var ta = form.querySelector('[name="besonderheiten"]');
        document.getElementById('preview-besonderheiten').value = ta ? ta.value : '';
        var prev = document.getElementById('preview-form');
        prev.querySelectorAll('.dynamic-preview').forEach(function (n) { n.remove(); });
        ['dosenfutter','trockenfutter','fleisch','diaet'].forEach(function (k) {
            var sel = form.querySelector('[name="futter_' + k + '_freq"]');
            if (sel && sel.value) {
                var j = document.createElement('input'); j.type = 'hidden'; j.name = 'futter_' + k + '_freq'; j.value = sel.value; j.className = 'dynamic-preview'; prev.appendChild(j);
            }
        });
        var badChoice = form.querySelector('[name="bad_choice"]');
        if (badChoice && badChoice.value) {
            var bc = document.createElement('input'); bc.type = 'hidden'; bc.name = 'bad_choice'; bc.value = badChoice.value; bc.className = 'dynamic-preview'; prev.appendChild(bc);
        }
        form.querySelectorAll('#med-item-rows .med-item-row').forEach(function (row, idx) {
            var noteIn = row.querySelector('input[name*="[note]"]');
            var freqIn = row.querySelector('select[name*="[freq]"]');
            if (noteIn) { var h = document.createElement('input'); h.type = 'hidden'; h.name = 'med_items[' + idx + '][note]'; h.value = noteIn.value; h.className = 'dynamic-preview'; prev.appendChild(h); }
            if (freqIn) { var h2 = document.createElement('input'); h2.type = 'hidden'; h2.name = 'med_items[' + idx + '][freq]'; h2.value = freqIn.value; h2.className = 'dynamic-preview'; prev.appendChild(h2); }
        });
    }
    document.getElementById('btn-preview-pdf')?.addEventListener('click', function () {
        syncPreviewHidden();
        document.getElementById('preview-form').submit();
    });

    function resizeCanvas(canvas) {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var w = canvas.offsetWidth;
        var h = canvas.offsetHeight;
        canvas.width = w * ratio;
        canvas.height = h * ratio;
        var ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);
    }

    function setupPad(canvasId, clearId, formId, inputId) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return null;
        resizeCanvas(canvas);
        var pad = new SignaturePad(canvas, { minWidth: 0.6, maxWidth: 2.2, backgroundColor: 'rgb(255,255,255)' });
        document.getElementById(clearId)?.addEventListener('click', function () { pad.clear(); });
        document.getElementById(formId)?.addEventListener('submit', function (e) {
            if (pad.isEmpty()) { e.preventDefault(); alert('Bitte unterschreiben.'); return; }
            document.getElementById(inputId).value = pad.toDataURL('image/png');
        });
        return pad;
    }

    document.getElementById('form-sign-intake')?.addEventListener('submit', function () { mirrorFormToIntakeSign(); });
    setupPad('canvas-intake', 'clear-intake', 'form-sign-intake', 'input-signature-intake');

    function reindexMedRows() {
        var container = document.getElementById('med-item-rows');
        if (!container) return;
        container.querySelectorAll('.med-item-row').forEach(function (row, idx) {
            var noteIn = row.querySelector('input[type="text"]');
            var freqIn = row.querySelector('select');
            if (noteIn) noteIn.name = 'med_items[' + idx + '][note]';
            if (freqIn) freqIn.name = 'med_items[' + idx + '][freq]';
        });
        var n = container.querySelectorAll('.med-item-row').length;
        container.querySelectorAll('.med-remove-col').forEach(function (col) {
            if (n < 2) col.classList.add('d-none'); else col.classList.remove('d-none');
        });
    }

    document.getElementById('med-item-add')?.addEventListener('click', function () {
        var container = document.getElementById('med-item-rows');
        var first = container && container.querySelector('.med-item-row');
        if (!container || !first) return;
        var copy = first.cloneNode(true);
        copy.querySelectorAll('input[type="text"]').forEach(function (i) { i.value = ''; });
        copy.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
        var rem = copy.querySelector('.med-remove-col');
        if (rem) rem.classList.remove('d-none');
        container.appendChild(copy);
        reindexMedRows();
    });

    document.getElementById('med-item-rows')?.addEventListener('click', function (e) {
        var btn = e.target.closest('.med-item-remove');
        if (!btn) return;
        e.preventDefault();
        var row = btn.closest('.med-item-row');
        var container = document.getElementById('med-item-rows');
        if (!row || !container) return;
        if (container.querySelectorAll('.med-item-row').length <= 1) {
            row.querySelectorAll('input[type="text"]').forEach(function (i) { i.value = ''; });
            row.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
            return;
        }
        row.remove();
        reindexMedRows();
    });
    setupPad('canvas-checkout', 'clear-checkout', 'form-sign-checkout', 'input-signature-checkout');

    function mirrorFormToIntakeSign() {
        var form = document.getElementById('pflegevertrag-form');
        var signForm = document.getElementById('form-sign-intake');
        if (!form || !signForm) return;
        var ta = form.querySelector('[name="besonderheiten"]');
        document.getElementById('sign-intake-besonderheiten').value = ta ? ta.value : '';
        ['dosenfutter','trockenfutter','fleisch','diaet'].forEach(function (k) {
            var sel = form.querySelector('[name="futter_' + k + '_freq"]');
            var fqEl = signForm.querySelector('.mirror-futter-freq-' + k);
            if (fqEl) fqEl.value = (sel && sel.value) ? sel.value : '';
        });
        var badChoice = form.querySelector('[name="bad_choice"]');
        signForm.querySelector('.mirror-bad-choice').value = badChoice ? badChoice.value : '';
        var mirror = signForm.querySelector('#intake-med-mirror');
        if (mirror) {
            mirror.innerHTML = '';
            form.querySelectorAll('#med-item-rows .med-item-row').forEach(function (row, idx) {
                var noteIn = row.querySelector('input[name*="[note]"]');
                var freqIn = row.querySelector('select[name*="[freq]"]');
                if (noteIn) { var h = document.createElement('input'); h.type = 'hidden'; h.name = 'med_items[' + idx + '][note]'; h.value = noteIn.value; mirror.appendChild(h); }
                if (freqIn) { var h2 = document.createElement('input'); h2.type = 'hidden'; h2.name = 'med_items[' + idx + '][freq]'; h2.value = freqIn.value; mirror.appendChild(h2); }
            });
        }
    }

    var openEmailBtn = document.getElementById('btn-open-email-confirm');
    if (openEmailBtn) {
        openEmailBtn.addEventListener('click', function () {
            var modalEl = document.getElementById('emailConfirmModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    }

    var confirmEmailBtn = document.getElementById('btn-confirm-email-send');
    if (confirmEmailBtn) {
        confirmEmailBtn.addEventListener('click', function () {
            confirmEmailBtn.disabled = true;
            confirmEmailBtn.querySelector('.spinner-border')?.classList.remove('d-none');
            document.getElementById('email-send-form')?.submit();
        });
    }

    // Disable buttons on form submit to prevent double submit.
    ['pflegevertrag-form', 'form-sign-intake', 'form-sign-checkout', 'email-send-form'].forEach(function (id) {
        var f = document.getElementById(id);
        if (!f) return;
        f.addEventListener('submit', function () {
            f.querySelectorAll('button, input[type="submit"]').forEach(function (btn) {
                if (btn.disabled) return;
                btn.disabled = true;
                btn.querySelector?.('.spinner-border')?.classList.remove('d-none');
            });
        });
    });

    // For non-submit actions (preview button), disable briefly.
    document.getElementById('btn-preview-pdf')?.addEventListener('click', function (e) {
        var b = e.currentTarget;
        if (!b || b.disabled) return;
        b.disabled = true;
        b.querySelector?.('.spinner-border')?.classList.remove('d-none');
        // Re-enable after a short delay in case popups are blocked.
        setTimeout(function () {
            b.disabled = false;
            b.querySelector?.('.spinner-border')?.classList.add('d-none');
        }, 2500);
    });
})();
</script>
@endsection
