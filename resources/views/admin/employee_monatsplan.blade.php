@extends('admin.layouts.app')

@section('title')
    <title>Monatsplan</title>
@endsection

@section('extra_css')
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }

    table {
      width: 60%;
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 5px;
      text-align: center;
    }

    th {
      background-color: #f4f4f4;
      font-weight: bold;
    }

    td {
      vertical-align: top;
    }

    .shift-column {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    ul {
      padding: 0;
      margin: 5px 0;
      list-style: none;
    }

    ul li {
      margin: 5px 0;
    }

    button {
      margin-top: 5px;
      padding: 5px 10px;
      background-color: #f4f4f4;
      color: #007bff;
      border: none;
      border-radius: 4px;
      cursor: pointer;

    }


    .month-navigation {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    font-family: Arial, sans-serif;
    align-items:center;
    }

    .btn-arrow{
    margin:0;
    }


    .month-display {
        font-size: 18px;
        font-weight: bold;
        margin: 0 50px; /* Reduced the margin to decrease the gap between the arrows */
    }

  </style>
@endsection

@section('body')


    <div class="month-navigation">

      <form action="{{route('admin.employee.track.monatsplan')}}" method="GET">
        <input type="hidden" name="month" value="{{ $currentMonth }}">
        <input type="hidden" name="action" value="prev">

        <button type="submit" class="btn-arrow"><i class="fas fa-arrow-left"></i></button> <!-- Previous month -->
      </form>
      <span id="monthDisplay "class="month-display text-danger">{{$deMonth}}</span> <!-- Current month -->
      <form action="{{ route('admin.employee.track.monatsplan') }}" method="GET">
        <input type="hidden" name="month" value="{{ $currentMonth }}">
        <input type="hidden" name="action" value="next">
        <button type="submit" class="btn-arrow"><i class="fas fa-arrow-right"></i></button> <!-- Next month -->
    </form>

    </div>

  <br>
    <div class="table">
      <table class='table'>
        <thead>
            <tr>
                <th>Tag</th> <!-- Day -->
                <th>Frühschicht</th> <!-- Morning Shift -->
                <th>Nachtschicht</th> <!-- Evening Shift -->
            </tr>
        </thead>
        <tbody>
          @for ($day = 1; $day <= 31; $day++)
              <tr id="dayFetch-{{ $day }}">
                  <td>{{ $day }}</td>

                  {{-- Frühschicht (Morning Shift) --}}
                  <td>
                      <div class="clickable-shift" data-bs-toggle="modal" data-bs-target="#shiftModal"
                      onclick="openModal('morning', '{{ $day }}')" style="cursor: pointer;">
                          @php
                              $currentMonthEvents = $events[$currentMonth] ?? collect();
                              $morningEmployees = $currentMonthEvents->filter(function($event) use ($day) {
                                  return $event->shift === 'morning' && \Carbon\Carbon::parse($event->start)->day == $day;
                              });
                          @endphp
                          @if ($morningEmployees->isNotEmpty())
                              @foreach ($morningEmployees as $event)
                                  <div>
                                      (<strong>{{ \Carbon\Carbon::parse($event->start)->format('H:i') }}</strong>) -
                                      (<strong>{{ \Carbon\Carbon::parse($event->end)->format('H:i') }}</strong>)
                                      {{ $event->status }} (<strong>{{ $event->user->name }}</strong>)
                                  </div>
                              @endforeach
                          @else
                          <button
                              class="btn no-bg w-100 py-2 fw-bold"
                              data-bs-toggle="modal"
                              style="color:#ddd;background: #eaeaea;border-radius:0"
                              data-bs-target="#shiftModal"
                              data-day="{{ $day }}"
                              data-month="{{ $currentMonth }}">
                              Add
                          </button>

                          @endif
                      </div>
                  </td>

                  {{-- Spätschicht (Evening Shift) --}}
                  <td>
                    <div class="clickable-shift" data-bs-toggle="modal" data-bs-target="#shiftModal"
                    onclick="openModal('evening', '{{ $day }}')" style="cursor: pointer;">
                         @php
                         // Get the events for the current month
                         $currentMonthEvents = $events[$currentMonth] ?? collect();
                         $eveningEmployees = $currentMonthEvents->filter(function($event) use ($day) {
                             return $event->shift === 'evening' && \Carbon\Carbon::parse($event->start)->day == $day;
                         });
                     @endphp
                        @if ($eveningEmployees->isNotEmpty())
                            {{-- Loop through the evening shifts and display employee names --}}
                            @foreach ($eveningEmployees as $event)
                                <div>
                                    (<strong>{{ \Carbon\Carbon::parse($event->start)->format('H:i') }}</strong>) -
                                    (<strong>{{ \Carbon\Carbon::parse($event->end)->format('H:i') }}</strong>)
                                    {{ $event->status }} (<strong>{{ $event->user->name }}</strong>)
                                </div>
                            @endforeach
                        @else
                        <button
                              class="btn no-bg w-100 py-2 fw-bold"
                              data-bs-toggle="modal"
                              style="color:#ddd;background: #eaeaea;border-radius:0"
                              data-bs-target="#shiftModal"
                              data-day="{{ $day }}"
                              data-month="{{ $currentMonth }}">
                              Add
                          </button>
                     <!-- Add -->
                        @endif
                    </div>
                </td>

              </tr>
          @endfor
        </tbody>
    </table>

    </div>

    <!-- Modal for adding shift -->
    <div class="modal fade" id="shiftModal" tabindex="-1" aria-labelledby="shiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shiftModalLabel">Schicht hinzufügen</h5> <!-- Add Shift -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form action="{{ route('admin.storeEmployees.monatsplan') }}" method="POST">
                @csrf
                <!-- Hidden input to store the selected day -->
                <input type="hidden" name="daySelected" id="daySelected">

                <!-- Select Employees -->
                <div class="mb-3">
                    <label for="employees" class="form-label">Mitarbeiter auswählen</label>
                    <select id="employees" name="employees[]" class="form-select" style="height:200px;font-size:17px;" multiple required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" style="margin-bottom: 6px;font-weight:600">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date & Time -->
                <div class="mb-3">
                    <label for="startDateTime" class="form-label">Startdatum und -zeit</label>
                    <input
                        type="datetime-local"
                        name="startDateTime"
                        id="startDateTime"
                        class="form-control"
                        required
                        min=""
                        max=""
                    >
                </div>

                <!-- End Date & Time -->
                <div class="mb-3">
                    <label for="endDateTime" class="form-label">Enddatum und -zeit</label>
                    <input
                        type="datetime-local"
                        name="endDateTime"
                        id="endDateTime"
                        class="form-control"
                        required
                        min=""
                        max=""
                    >
                </div>

                <!-- Shift Selection -->
                <div class="mb-3">
                    <label for="shiftType" class="form-label">Schicht</label>
                    <select id="shiftType" name="shiftType" class="form-select" required>
                        <option value="morning">Morgen</option> <!-- Morning -->
                        <option value="evening">Abend</option> <!-- Evening -->
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">Schicht speichern</button>
            </form>

            </div>
        </div>
    </div>
</div>


@endsection

@section('extra_js')
<script>
  function openModal(shiftType, day) {
    document.getElementById('shiftType').value = shiftType;

    // Get the current year and month
    var cur_month = @json($currentMonth);
    if(cur_month)
    {
      const [monthName, year] = cur_month.split(' ');
      const monthIndex = new Date(`${monthName} ${day}, 2021`).getMonth();
      var selectedDate = new Date(year, monthIndex, day);

    }
    else{
      const now = new Date();
      var selectedDate = new Date(now.getFullYear(), now.getMonth(), day);
    }

    // Format the date as "YYYY-MM-DD"
    const year = selectedDate.getFullYear();
    const month = String(selectedDate.getMonth() + 1).padStart(2, '0'); // Adjust for zero-based month
    const date = String(selectedDate.getDate()).padStart(2, '0');
    const dateOnly = `${year}-${month}-${date}`;


    document.getElementById('daySelected').value = day;

    // Default start and end times
    const defaultStartTime = shiftType === 'morning' ? "09:00" : "14:00";
    const defaultEndTime = shiftType === 'morning' ? "13:00" : "18:00";

    // Populate modal inputs with calculated values
    document.getElementById('startDateTime').value = `${dateOnly}T${defaultStartTime}`;
    document.getElementById('startDateTime').min = `${dateOnly}T00:00`;
    document.getElementById('startDateTime').max = `${dateOnly}T23:59`;

    document.getElementById('endDateTime').value = `${dateOnly}T${defaultEndTime}`;
    document.getElementById('endDateTime').min = `${dateOnly}T00:00`;
    document.getElementById('endDateTime').max = `${dateOnly}T23:59`;

    // Set the shiftType in dropdown (hide if it's a morning or evening shift)
    const shiftTypeDropdown = document.getElementById('shiftType');
    const shiftTypeLabel = document.querySelector('label[for="shiftType"]');


    if (shiftType === 'morning' || shiftType === 'evening') {
        shiftTypeDropdown.value = shiftType; // Set the default shift type
        shiftTypeDropdown.style.display = 'none'; // Hide the dropdown
        shiftTypeLabel.style.display = 'none'; // Hide the label
    } else {
        shiftTypeDropdown.style.display = 'block';
        shiftTypeLabel.style.display = 'block';
    }
  }
</script>

@endsection
