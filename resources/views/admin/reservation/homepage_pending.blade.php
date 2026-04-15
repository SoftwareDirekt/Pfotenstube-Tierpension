@extends('admin.layouts.app')

@section('title')
    <title>Pfotenstube-Anfragen</title>
@endsection

@section('body')
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pfotenstube-Anfragen</h5>
                <a href="{{ route('admin.reservation', ['sl' => 3]) }}" class="btn btn-sm btn-outline-primary">Zur Reservierungsübersicht</a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Hund</th>
                            <th>Kunde</th>
                            <th>E-Mail</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th class="text-end">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $res)
                            <tr>
                                <td class="fw-semibold">{{ $res->dog->name ?? '—' }}</td>
                                <td>{{ $res->dog->customer->name ?? '—' }}</td>
                                <td>{{ $res->dog->customer->email ?? '—' }}</td>
                                <td>{{ $res->checkin_date->format('d.m.Y H:i') }}</td>
                                <td>{{ $res->checkout_date->format('d.m.Y H:i') }}</td>
                                <td class="text-end homepage-pending-actions" style="white-space:nowrap;">
                                    <button type="button"
                                            class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 homepage-pending-confirm"
                                            data-res-id="{{ $res->id }}"
                                            data-dog-name="{{ $res->dog->name ?? '' }}"
                                            data-reg-plan="{{ $res->dog->reg_plan ?? '' }}"
                                            data-day-plan="{{ $res->dog->day_plan ?? '' }}"
                                            data-checkin="{{ $res->checkin_date->format('Y-m-d') }}"
                                            data-checkout="{{ $res->checkout_date->format('Y-m-d') }}">
                                        <span class="pending-btn-label">Bestätigen</span>
                                        <span class="spinner-border spinner-border-sm pending-spinner d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                    <form action="{{ route('admin.reservation.homepage.pending.reject') }}" method="POST" class="d-inline homepage-pending-form homepage-pending-reject ms-1">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $res->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                            <span class="pending-btn-label">Ablehnen</span>
                                            <span class="spinner-border spinner-border-sm pending-spinner d-none" role="status" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">Keine ausstehenden Online-Anfragen.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmHomepageReservationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reservierung bestätigen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.reservation.homepage.pending.confirm') }}" method="POST" id="confirmHomepageReservationForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="confirmReservationId" value="">
                        <div class="mb-3">
                            <label class="form-label">Hund</label>
                            <input type="text" class="form-control" id="confirmReservationDog" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preisplan <span class="text-danger">*</span></label>
                            <select name="plan_id" id="confirmReservationPlan" class="form-select" required>
                                <option value="" selected disabled>Preisplan auswählen</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-success">Bestätigen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        (function () {
            var rejectMsg = 'Anfrage wirklich ablehnen? Der Kunde wird per E-Mail informiert.';
            document.querySelectorAll('.homepage-pending-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    var cell = form.closest('.homepage-pending-actions');
                    if (cell && cell.querySelector('.homepage-pending-form[data-pending-submitting="1"]')) {
                        e.preventDefault();
                        return;
                    }
                    if (form.classList.contains('homepage-pending-reject')) {
                        if (!window.confirm(rejectMsg)) {
                            e.preventDefault();
                            return;
                        }
                    }
                    if (!cell) {
                        return;
                    }
                    cell.querySelectorAll('.homepage-pending-form').forEach(function (f) {
                        f.setAttribute('data-pending-submitting', '1');
                    });
                    cell.querySelectorAll('.homepage-pending-form button[type="submit"]').forEach(function (btn) {
                        btn.disabled = true;
                    });
                    var activeBtn = form.querySelector('button[type="submit"]');
                    if (activeBtn) {
                        var spin = activeBtn.querySelector('.pending-spinner');
                        var lbl = activeBtn.querySelector('.pending-btn-label');
                        if (spin) spin.classList.remove('d-none');
                        if (lbl) lbl.classList.add('d-none');
                    }
                });
            });

            var modalEl = document.getElementById('confirmHomepageReservationModal');
            var confirmModal = modalEl ? new bootstrap.Modal(modalEl) : null;
            document.querySelectorAll('.homepage-pending-confirm').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var resId = btn.getAttribute('data-res-id');
                    var dogName = btn.getAttribute('data-dog-name') || '';
                    var regPlan = btn.getAttribute('data-reg-plan');
                    var dayPlan = btn.getAttribute('data-day-plan');
                    var checkin = btn.getAttribute('data-checkin');
                    var checkout = btn.getAttribute('data-checkout');

                    var selectedPlan = '';
                    if (checkin && checkout) {
                        var checkinDate = new Date(checkin + 'T00:00:00');
                        var checkoutDate = new Date(checkout + 'T00:00:00');
                        var diffDays = Math.floor((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
                        if (diffDays === 0) {
                            selectedPlan = dayPlan || regPlan;
                        } else {
                            selectedPlan = regPlan || dayPlan;
                        }
                    } else {
                        selectedPlan = regPlan || dayPlan;
                    }

                    var idInput = document.getElementById('confirmReservationId');
                    var dogInput = document.getElementById('confirmReservationDog');
                    var planSelect = document.getElementById('confirmReservationPlan');

                    if (idInput) idInput.value = resId || '';
                    if (dogInput) dogInput.value = dogName;
                    if (planSelect) {
                        planSelect.value = selectedPlan || '';
                    }

                    confirmModal?.show();
                });
            });
        })();
    </script>
@endsection
