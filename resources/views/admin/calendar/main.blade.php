@extends('admin.layouts.app')

@section('title')
    <title>Kalender</title>
@endsection

@section('extra_css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .fc-event { cursor: pointer; }
        .fc-daygrid-day:hover { background-color: rgba(0, 0, 0, 0.03); }
    </style>
@endsection

@section('body')
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Event Modal --}}
    <div class="modal fade" id="createEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ereignis hinzufügen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <form id="createEventForm">
                    <div class="modal-body">
                        <input type="hidden" id="createDate" name="date">
                        <div class="mb-3">
                            <label class="form-label fw-medium" id="createDateDisplay"></label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Typ <span class="text-danger">*</span></label>
                            <select class="form-select" id="createStatus" name="status" required>
                                <option value="">Bitte wählen</option>
                                <option value="Arbeit">Arbeit</option>
                                <option value="Urlaub">Urlaub</option>
                                <option value="Krankenstand">Krankenstand</option>
                                <option value="Andere">Andere</option>
                            </select>
                        </div>

                        {{-- Notes field for Andere --}}
                        <div class="mb-3" id="createNotesField" style="display: none;">
                            <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="createNotes" name="notes" rows="3" placeholder="z.B. Meeting mit..., Arzt kommt..."></textarea>
                        </div>

                        <div class="mb-3" id="createUserField">
                            <label class="form-label">Mitarbeiter <span class="text-danger" id="createUserRequired">*</span></label>
                            <select class="form-select" id="createUser" name="uid">
                                <option value="">Bitte wählen</option>
                                @foreach(App\Models\User::where('role', 2)->orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="createTimeFields">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Startzeit <span class="text-danger" id="createStartRequired">*</span></label>
                                    <input type="text" class="form-control" id="createStartTime" name="start_time" placeholder="HH:mm">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Endzeit <span class="text-danger" id="createEndRequired">*</span></label>
                                    <input type="text" class="form-control" id="createEndTime" name="end_time" placeholder="HH:mm">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Schicht <span class="text-danger" id="createShiftRequired">*</span></label>
                                <select class="form-select" id="createShift" name="shift">
                                    <option value="">Bitte wählen</option>
                                    <option value="morning">Frühschicht</option>
                                    <option value="evening">Spätschicht</option>
                                </select>
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

    {{-- Edit Event Modal --}}
    <div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ereignis bearbeiten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <form id="editEventForm">
                    <div class="modal-body">
                        <input type="hidden" id="editEventId" name="id">
                        <input type="hidden" id="editDate" name="date">
                        <div class="mb-3">
                            <label class="form-label fw-medium" id="editDateDisplay"></label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Typ <span class="text-danger">*</span></label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="Arbeit">Arbeit</option>
                                <option value="Urlaub">Urlaub</option>
                                <option value="Krankenstand">Krankenstand</option>
                                <option value="Andere">Andere</option>
                            </select>
                        </div>

                        {{-- Notes field for Andere --}}
                        <div class="mb-3" id="editNotesField" style="display: none;">
                            <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="3" placeholder="z.B. Meeting mit..., Arzt kommt..."></textarea>
                        </div>

                        <div class="mb-3" id="editUserField">
                            <label class="form-label">Mitarbeiter <span class="text-danger" id="editUserRequired">*</span></label>
                            <select class="form-select" id="editUser" name="uid">
                                <option value="">Bitte wählen</option>
                                @foreach(App\Models\User::where('role', 2)->orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="editTimeFields">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Startzeit <span class="text-danger" id="editStartRequired">*</span></label>
                                    <input type="text" class="form-control" id="editStartTime" name="start_time" placeholder="HH:mm">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Endzeit <span class="text-danger" id="editEndRequired">*</span></label>
                                    <input type="text" class="form-control" id="editEndTime" name="end_time" placeholder="HH:mm">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Schicht <span class="text-danger" id="editShiftRequired">*</span></label>
                                <select class="form-select" id="editShift" name="shift">
                                    <option value="">Bitte wählen</option>
                                    <option value="morning">Frühschicht</option>
                                    <option value="evening">Spätschicht</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column">
                        <div class="d-flex w-100 gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button type="submit" class="btn btn-primary">Aktualisieren</button>
                        </div>
                        <hr class="w-100 my-3">
                        <button type="button" class="btn btn-outline-danger w-100" id="deleteEventBtn">
                            <i class="bx bx-trash me-1"></i> Ereignis löschen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '{{ csrf_token() }}';
            
            const calendarEl = document.getElementById('calendar');
            const createModal = new bootstrap.Modal(document.getElementById('createEventModal'));
            const editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
            
            // Flatpickr config
            const flatpickrConfig = {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 15,
                allowInput: true,
                locale: 'de'
            };
            
            const createStartPicker = flatpickr('#createStartTime', flatpickrConfig);
            const createEndPicker = flatpickr('#createEndTime', flatpickrConfig);
            const editStartPicker = flatpickr('#editStartTime', flatpickrConfig);
            const editEndPicker = flatpickr('#editEndTime', flatpickrConfig);

            // Toggle fields based on status
            function toggleFields(status, isEdit = false) {
                const prefix = isEdit ? 'edit' : 'create';
                const timeFieldsEl = document.getElementById(`${prefix}TimeFields`);
                const notesFieldEl = document.getElementById(`${prefix}NotesField`);
                const userFieldEl = document.getElementById(`${prefix}UserField`);
                const notesEl = document.getElementById(`${prefix}Notes`);
                const userEl = document.getElementById(`${prefix}User`);
                const startTimeEl = document.getElementById(`${prefix}StartTime`);
                const endTimeEl = document.getElementById(`${prefix}EndTime`);
                const shiftEl = document.getElementById(`${prefix}Shift`);
                
                // Required indicators
                const userReqEl = document.getElementById(`${prefix}UserRequired`);
                const startReqEl = document.getElementById(`${prefix}StartRequired`);
                const endReqEl = document.getElementById(`${prefix}EndRequired`);
                const shiftReqEl = document.getElementById(`${prefix}ShiftRequired`);
                
                const isAndere = status === 'Andere';
                const isAllDay = ['Urlaub', 'Krankenstand'].includes(status);
                
                // Show/hide notes field
                notesFieldEl.style.display = isAndere ? 'block' : 'none';
                notesEl.required = isAndere;
                
                // Show/hide time fields
                if (isAllDay) {
                    timeFieldsEl.style.display = 'none';
                    startTimeEl.required = false;
                    endTimeEl.required = false;
                    shiftEl.required = false;
                } else if (isAndere) {
                    timeFieldsEl.style.display = 'block';
                    startTimeEl.required = false;
                    endTimeEl.required = false;
                    shiftEl.required = false;
                    // Update required indicators
                    startReqEl.style.display = 'none';
                    endReqEl.style.display = 'none';
                    shiftReqEl.style.display = 'none';
                } else {
                    timeFieldsEl.style.display = 'block';
                    startTimeEl.required = true;
                    endTimeEl.required = true;
                    shiftEl.required = true;
                    startReqEl.style.display = 'inline';
                    endReqEl.style.display = 'inline';
                    shiftReqEl.style.display = 'inline';
                }
                
                // User field handling
                if (isAndere) {
                    userEl.required = false;
                    userReqEl.style.display = 'none';
                } else {
                    userEl.required = true;
                    userReqEl.style.display = 'inline';
                }
            }

            // Event type change handlers
            document.getElementById('createStatus').addEventListener('change', function() {
                toggleFields(this.value, false);
            });
            
            document.getElementById('editStatus').addEventListener('change', function() {
                toggleFields(this.value, true);
            });

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

            // Initialize FullCalendar
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'de',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Heute',
                    month: 'Monat',
                    week: 'Woche',
                    day: 'Tag'
                },
                dayHeaderFormat: { weekday: 'long' },
                selectable: true,
                selectMirror: true,
                allDaySlot: false,
                events: '{{ route("admin.events.index") }}',
                
                eventDidMount: function(info) {
                    // Add tooltip for "Andere" events with notes
                    if (info.event.extendedProps.status === 'Andere' && info.event.extendedProps.notes) {
                        $(info.el).attr('title', info.event.extendedProps.notes);
                        $(info.el).tooltip({
                            placement: 'top',
                            trigger: 'hover'
                        });
                    }
                },
                
                dateClick: function(info) {
                    resetCreateForm();
                    document.getElementById('createDate').value = info.dateStr;
                    document.getElementById('createDateDisplay').textContent = formatDateDisplay(info.dateStr);
                    createModal.show();
                },
                
                eventClick: function(info) {
                    loadEventForEdit(info.event.id);
                }
            });
            
            calendar.render();

            // Reset create form
            function resetCreateForm() {
                document.getElementById('createEventForm').reset();
                document.getElementById('createTimeFields').style.display = 'block';
                document.getElementById('createNotesField').style.display = 'none';
                document.getElementById('createUserRequired').style.display = 'inline';
                document.getElementById('createStartRequired').style.display = 'inline';
                document.getElementById('createEndRequired').style.display = 'inline';
                document.getElementById('createShiftRequired').style.display = 'inline';
                createStartPicker.clear();
                createEndPicker.clear();
            }

            // Load event data for editing
            function loadEventForEdit(eventId) {
                fetch(`{{ url('admin/events') }}/${eventId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Ereignis nicht gefunden');
                    return response.json();
                })
                .then(data => {
                    document.getElementById('editEventId').value = data.id;
                    document.getElementById('editDate').value = data.date;
                    document.getElementById('editDateDisplay').textContent = formatDateDisplay(data.date);
                    document.getElementById('editStatus').value = data.status;
                    document.getElementById('editUser').value = data.uid || '';
                    document.getElementById('editNotes').value = data.notes || '';
                    
                    // Set time values
                    if (data.start_time && !['Urlaub', 'Krankenstand'].includes(data.status)) {
                        editStartPicker.setDate(data.start_time, true, 'H:i');
                        editEndPicker.setDate(data.end_time, true, 'H:i');
                        document.getElementById('editShift').value = data.shift || '';
                    } else {
                        editStartPicker.clear();
                        editEndPicker.clear();
                        document.getElementById('editShift').value = '';
                    }
                    
                    toggleFields(data.status, true);
                    editModal.show();
                })
                .catch(error => {
                    alert('Fehler beim Laden des Ereignisses: ' + error.message);
                });
            }

            // Create event form submission
            document.getElementById('createEventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                const status = data.status;
                const isAndere = status === 'Andere';
                const isAllDay = ['Urlaub', 'Krankenstand'].includes(status);
                
                // Validate based on type
                if (isAndere) {
                    if (!data.notes || !data.notes.trim()) {
                        alert('Bitte Beschreibung eingeben.');
                        return;
                    }
                } else {
                    if (!data.uid) {
                        alert('Bitte Mitarbeiter auswählen.');
                        return;
                    }
                    if (!isAllDay) {
                        if (!data.start_time || !data.end_time) {
                            alert('Bitte Start- und Endzeit eingeben.');
                            return;
                        }
                        if (!data.shift) {
                            alert('Bitte Schicht auswählen.');
                            return;
                        }
                    }
                }
                
                fetch('{{ route("admin.events.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Fehler beim Erstellen'); });
                    }
                    return response.json();
                })
                .then(result => {
                    createModal.hide();
                    calendar.refetchEvents();
                    resetCreateForm();
                })
                .catch(error => {
                    alert(error.message);
                });
            });

            // Edit event form submission
            document.getElementById('editEventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const eventId = document.getElementById('editEventId').value;
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                const status = data.status;
                const isAndere = status === 'Andere';
                const isAllDay = ['Urlaub', 'Krankenstand'].includes(status);
                
                // Validate based on type
                if (isAndere) {
                    if (!data.notes || !data.notes.trim()) {
                        alert('Bitte Beschreibung eingeben.');
                        return;
                    }
                } else {
                    if (!data.uid) {
                        alert('Bitte Mitarbeiter auswählen.');
                        return;
                    }
                    if (!isAllDay) {
                        if (!data.start_time || !data.end_time) {
                            alert('Bitte Start- und Endzeit eingeben.');
                            return;
                        }
                        if (!data.shift) {
                            alert('Bitte Schicht auswählen.');
                            return;
                        }
                    }
                }
                
                fetch(`{{ url('admin/events') }}/${eventId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Fehler beim Aktualisieren'); });
                    }
                    return response.json();
                })
                .then(result => {
                    editModal.hide();
                    calendar.refetchEvents();
                })
                .catch(error => {
                    alert(error.message);
                });
            });

            // Delete event
            document.getElementById('deleteEventBtn').addEventListener('click', function() {
                const eventId = document.getElementById('editEventId').value;
                
                if (!confirm('Möchten Sie dieses Ereignis wirklich löschen?')) {
                    return;
                }
                
                fetch(`{{ url('admin/events') }}/${eventId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Fehler beim Löschen'); });
                    }
                    return response.json();
                })
                .then(result => {
                    editModal.hide();
                    calendar.refetchEvents();
                })
                .catch(error => {
                    alert(error.message);
                });
            });

            // Reset forms when modals are hidden
            document.getElementById('createEventModal').addEventListener('hidden.bs.modal', resetCreateForm);
            
            document.getElementById('editEventModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('editEventForm').reset();
                document.getElementById('editTimeFields').style.display = 'block';
                document.getElementById('editNotesField').style.display = 'none';
                editStartPicker.clear();
                editEndPicker.clear();
            });
        });
    </script>
@endsection
