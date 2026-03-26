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
                                    <form action="{{ route('admin.reservation.homepage.pending.confirm') }}" method="POST" class="d-inline homepage-pending-form homepage-pending-confirm">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $res->id }}">
                                        <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                            <span class="pending-btn-label">Bestätigen</span>
                                            <span class="spinner-border spinner-border-sm pending-spinner d-none" role="status" aria-hidden="true"></span>
                                        </button>
                                    </form>
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
        })();
    </script>
@endsection
