@extends('admin.layouts.app')

@section('title')
    <title>Monatsplan</title>
@endsection

@section('extra_css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .monatsplan-table {
        width: 100%;
        border-collapse: collapse;
    }
    .monatsplan-table th,
    .monatsplan-table td {
        border: 1px solid #dee2e6;
        padding: 8px;
        text-align: center;
        vertical-align: top;
    }
    .monatsplan-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .monatsplan-table td.day-column {
        width: 60px;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .shift-cell {
        min-height: 40px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .shift-cell:hover {
        background-color: rgba(0,0,0,0.03);
    }
    .shift-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        margin: 2px;
        font-size: 13px;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .shift-pill:hover {
        opacity: 0.85;
    }
    .shift-pill .time {
        font-weight: 600;
    }
    .shift-pill .name {
        font-weight: 400;
    }
    .add-shift-btn {
        color: #ccc;
        background: #f5f5f5;
        border: 1px dashed #ddd;
        padding: 6px 16px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .add-shift-btn:hover {
        background: #e9ecef;
        color: #666;
        border-color: #ccc;
    }
    .month-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
    }
    .month-nav .month-title {
        font-size: 1.5rem;
        font-weight: 600;
        min-width: 200px;
        text-align: center;
    }
    .month-nav .btn-nav {
        background: none;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1.5rem;
    }
    .month-nav .btn-nav:hover {
        background: #e9ecef;
    }
    .weekend-row {
        background-color: #fafafa;
    }
</style>
@endsection

@section('body')
<div class="container-fluid px-4 py-4">
    <div class="card">
        <div class="card-body">
            {{-- Month Navigation --}}
            <div class="month-nav">
                <form action="{{ route('admin.employee.track.monatsplan') }}" method="GET" class="d-inline">
                    <input type="hidden" name="month" value="{{ $currentMonth }}">
                    <input type="hidden" name="action" value="prev">
                    <button type="submit" class="btn-nav text-success">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </form>
                
                <span class="month-title text-danger">{{ $germanMonth }}</span>
                
                <form action="{{ route('admin.employee.track.monatsplan') }}" method="GET" class="d-inline">
                    <input type="hidden" name="month" value="{{ $currentMonth }}">
                    <input type="hidden" name="action" value="next">
                    <button type="submit" class="btn-nav text-success">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            {{-- Monatsplan Table --}}
            <div class="table-responsive">
                <table class="monatsplan-table">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Frühschicht</th>
                            <th>Spätschicht</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $date = $startOfMonth->copy()->day($day);
                                $isWeekend = $date->isWeekend();
                                $dateStr = $date->format('Y-m-d');
                                $dayName = $date->locale('de')->shortDayName;
                                $morningEvents = $eventsByDayAndShift[$day]['morning'] ?? [];
                                $eveningEvents = $eventsByDayAndShift[$day]['evening'] ?? [];
                            @endphp
                            <tr class="{{ $isWeekend ? 'weekend-row' : '' }}">
                                <td class="day-column">
                                    <div>{{ $day }}</div>
                                    <small class="text-muted">{{ $dayName }}</small>
                                </td>
                                
                                {{-- Morning Shift --}}
                                <td class="shift-cell" 
                                    onclick="openCreateModal('morning', '{{ $dateStr }}')"
                                    data-day="{{ $day }}" 
                                    data-shift="morning">
                                    @if (count($morningEvents) > 0)
                                        @foreach ($morningEvents as $event)
                                            <div class="shift-pill" 
                                                 style="background-color: {{ $event->backgroundColor }}; color: {{ $event->textColor }};"
                                                 onclick="event.stopPropagation(); openEditModal({{ $event->id }}, '{{ $dateStr }}', 'morning', '{{ Carbon\Carbon::parse($event->start)->format('H:i') }}', '{{ Carbon\Carbon::parse($event->end)->format('H:i') }}', {{ $event->uid ?? 'null' }})">
                                                <span class="time">{{ Carbon\Carbon::parse($event->start)->format('H:i') }}-{{ Carbon\Carbon::parse($event->end)->format('H:i') }}</span>
                                                <span class="name">({{ $event->user?->name ?? 'Unbekannt' }})</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="add-shift-btn">+ Hinzufügen</span>
                                    @endif
                                </td>
                                
                                {{-- Evening Shift --}}
                                <td class="shift-cell" 
                                    onclick="openCreateModal('evening', '{{ $dateStr }}')"
                                    data-day="{{ $day }}" 
                                    data-shift="evening">
                                    @if (count($eveningEvents) > 0)
                                        @foreach ($eveningEvents as $event)
                                            <div class="shift-pill" 
                                                 style="background-color: {{ $event->backgroundColor }}; color: {{ $event->textColor }};"
                                                 onclick="event.stopPropagation(); openEditModal({{ $event->id }}, '{{ $dateStr }}', 'evening', '{{ Carbon\Carbon::parse($event->start)->format('H:i') }}', '{{ Carbon\Carbon::parse($event->end)->format('H:i') }}', {{ $event->uid ?? 'null' }})">
                                                <span class="time">{{ Carbon\Carbon::parse($event->start)->format('H:i') }}-{{ Carbon\Carbon::parse($event->end)->format('H:i') }}</span>
                                                <span class="name">({{ $event->user?->name ?? 'Unbekannt' }})</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="add-shift-btn">+ Hinzufügen</span>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Shift Modal --}}
<div class="modal fade" id="createShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schicht hinzufügen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form id="createShiftForm" action="{{ route('admin.monatsplan.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="createDate" name="date">
                    <input type="hidden" id="createShift" name="shift">
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium" id="createDateDisplay"></label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mitarbeiter <span class="text-danger">*</span></label>
                        <select class="form-select" id="createEmployees" name="employees[]" multiple required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Mehrfachauswahl möglich</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Startzeit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="createStartTime" name="start_time" placeholder="HH:mm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Endzeit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="createEndTime" name="end_time" placeholder="HH:mm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Hinzufügen</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Shift Modal --}}
<div class="modal fade" id="editShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schicht bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form id="editShiftForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="editDate" name="date">
                    <input type="hidden" id="editShift" name="shift">
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium" id="editDateDisplay"></label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mitarbeiter <span class="text-danger">*</span></label>
                        <select class="form-select" id="editEmployee" name="uid" required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Startzeit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editStartTime" name="start_time" placeholder="HH:mm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Endzeit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editEndTime" name="end_time" placeholder="HH:mm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column">
                    <div class="d-flex w-100 gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Aktualisieren</button>
                    </div>
                    <hr class="w-100 my-3">
                    <button type="button" class="btn btn-outline-danger w-100" id="deleteShiftBtn">
                        <i class="bx bx-trash me-1"></i> Schicht löschen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Form (hidden) --}}
<form id="deleteShiftForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('extra_js')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createModal = new bootstrap.Modal(document.getElementById('createShiftModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editShiftModal'));
    
    let currentEditId = null;
    
    // Flatpickr config
    const timeConfig = {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
        minuteIncrement: 15,
        allowInput: true,
        locale: 'de'
    };
    
    const createStartPicker = flatpickr('#createStartTime', timeConfig);
    const createEndPicker = flatpickr('#createEndTime', timeConfig);
    const editStartPicker = flatpickr('#editStartTime', timeConfig);
    const editEndPicker = flatpickr('#editEndTime', timeConfig);
    
    // Format date for display
    function formatDateDisplay(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('de-AT', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    // Get shift label
    function getShiftLabel(shift) {
        return shift === 'morning' ? 'Frühschicht' : 'Spätschicht';
    }
    
    // Default times for shifts
    function getDefaultTimes(shift) {
        if (shift === 'morning') {
            return { start: '06:00', end: '14:00' };
        }
        return { start: '14:00', end: '22:00' };
    }
    
    // Open create modal
    window.openCreateModal = function(shift, dateStr) {
        document.getElementById('createDate').value = dateStr;
        document.getElementById('createShift').value = shift;
        document.getElementById('createDateDisplay').textContent = formatDateDisplay(dateStr) + ' - ' + getShiftLabel(shift);
        
        // Reset form
        document.getElementById('createShiftForm').reset();
        document.getElementById('createDate').value = dateStr;
        document.getElementById('createShift').value = shift;
        
        // Reset employees select
        $('#createEmployees').val(null).trigger('change');
        
        // Set default times based on shift
        const defaults = getDefaultTimes(shift);
        createStartPicker.setDate(defaults.start, true, 'H:i');
        createEndPicker.setDate(defaults.end, true, 'H:i');
        
        createModal.show();
    };
    
    // Open edit modal
    window.openEditModal = function(eventId, dateStr, shift, startTime, endTime, uid) {
        currentEditId = eventId;
        
        document.getElementById('editDate').value = dateStr;
        document.getElementById('editShift').value = shift;
        document.getElementById('editDateDisplay').textContent = formatDateDisplay(dateStr) + ' - ' + getShiftLabel(shift);
        document.getElementById('editEmployee').value = uid;
        
        editStartPicker.setDate(startTime, true, 'H:i');
        editEndPicker.setDate(endTime, true, 'H:i');
        
        // Update form action
        document.getElementById('editShiftForm').action = '{{ url("admin/monatsplan") }}/' + eventId;
        
        editModal.show();
    };
    
    // Delete shift
    document.getElementById('deleteShiftBtn').addEventListener('click', function() {
        if (!currentEditId) return;
        
        if (confirm('Möchten Sie diese Schicht wirklich löschen?')) {
            const deleteForm = document.getElementById('deleteShiftForm');
            deleteForm.action = '{{ url("admin/monatsplan") }}/' + currentEditId;
            deleteForm.submit();
        }
    });
    
    // Reset modals on close
    document.getElementById('createShiftModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('createShiftForm').reset();
        $('#createEmployees').val(null).trigger('change');
        createStartPicker.clear();
        createEndPicker.clear();
    });
    
    document.getElementById('editShiftModal').addEventListener('hidden.bs.modal', function() {
        currentEditId = null;
        editStartPicker.clear();
        editEndPicker.clear();
    });
});
</script>
@endsection
