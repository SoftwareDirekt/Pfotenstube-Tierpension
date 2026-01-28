@extends('admin.layouts.app')
@section('title')
    <title>Mitarbeiterverfolgung</title>
@endsection
@section('extra_css')
    <style>

    </style>
@endsection
@section('body')
    <div class="px-4 flex-grow-1 container-p-y">
        <div class="row gy-4">
            <div class="card">
                <div class="row">
                    <div class="col-md-4 my-2">
                        <h5 class="card-header">Arbeitszeiterfassung</h5>
                    </div>
                    <hr>
                    <form class="row">
                        <div class="col-md-5">
                            <div class="form-floating form-floating-outline my-3">
                                <select name="month" class="form-control">
                                    @foreach(range(1, 12) as $month)
                                        <option
                                            value="{{ $month }}" {{$month == $selected_month ? 'selected' : ''}}>{{ __('months.' . date("F", mktime(0, 0, 0, $month, 10))) }}</option>
                                    @endforeach
                                </select>
                                <label for="type">Monat</label>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-floating form-floating-outline my-3">
                                <select name="year" class="form-control">
                                    @foreach(range(date('Y'), 2020) as $year)
                                        <option
                                            value="{{ $year }}" {{$year == $selected_year ? 'selected' : ''}}>{{ $year }}</option>
                                    @endforeach
                                </select>
                                <label for="type">Jahr</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-floating form-floating-outline my-4">
                                <button type="submit" class="btn btn-primary">Suche</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table" id="employeeTable">
                        <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gearbeitete Tage</th>
                            <th>Gesamtarbeitsstunden</th>
                            <th>Download</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @if(isset($employees))
                            @foreach ($employees as $employee)
                                <tr>
                                    <td>{{ str_pad($employee->id, 3, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->events->count() }}</td>
                                    <td>{{ number_format($employee->events->sum('hours_worked'), 2) }}</td>
                                    <td>
                                        <button
                                            class="btn btn-success btn-sm download-btn"
                                            data-employee-id="{{ $employee->id }}"
                                            data-month="{{ $selected_month }}"
                                            data-year="{{ $selected_year }}"
                                        >
                                            Arbeitszeiterfassung &nbsp; <i class="fa fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="14">No records found</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('extra_js')
    <script>
        $(document).ready(function () {
            $('#employeeTable').DataTable({
                "oLanguage": {
                    "sSearch": "Suche: "
                },
                language: {
                    'paginate': {
                        'previous': 'Zurück',
                        'next': 'Weiter'
                    }
                },
                pageLength: 50,
                lengthMenu: [
                    [100, 200, 500, 1000, -1],
                    [100, 200, 500, 1000, 'All']
                ],
                dom: '<"row"<"col-md-6"B><"col-md-6"f>>rtip',
                buttons: [{
                    extend: 'excel',
                    text: "Excel"
                },
                    {
                        extend: 'pdf',
                        text: 'PDF'
                    },
                    {
                        extend: 'print',
                        text: 'Drucken'
                    }

                ]
            });

        });


        $(function(){
            $(document).on('click', '.download-btn', function(e){
                e.preventDefault();

                const btn      = $(this);
                const employee = btn.data('employee-id');
                const month    = btn.data('month');
                const year     = btn.data('year');
                const token    = $('meta[name="csrf-token"]').attr('content');

                // Store original button HTML
                const originalHtml = btn.html();
                const originalDisabled = btn.prop('disabled');

                // Disable button and show spinner
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Verarbeitung...');

                $.ajax({
                    url: "{{ route('admin.employee.workingrecord') }}",
                    method: "POST",
                    data: {
                        _token:   token,
                        employee: employee,
                        month:    month,
                        year:     year
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(blob, status, xhr){
                        // Get filename from custom header or Content-Disposition header
                        let filename = `Arbeitszeit_${year}_${month}.pdf`; // Fallback
                        
                        // Try custom header first (more reliable)
                        const customFilename = xhr.getResponseHeader('X-Filename');
                        if (customFilename) {
                            filename = customFilename;
                        } else {
                            // Fallback to Content-Disposition header
                            const contentDisposition = xhr.getResponseHeader('Content-Disposition');
                            if (contentDisposition) {
                                const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                                if (filenameMatch && filenameMatch[1]) {
                                    filename = filenameMatch[1].replace(/['"]/g, '');
                                }
                            }
                        }

                        const fileURL = window.URL.createObjectURL(blob);
                        const a       = document.createElement('a');
                        a.href        = fileURL;
                        a.download    = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(fileURL);

                        // Re-enable button and restore original HTML
                        btn.prop('disabled', false);
                        btn.html(originalHtml);
                    },
                    error: function(xhr){
                        alert('Fehler beim Erstellen der PDF: ' + (xhr.responseText || xhr.statusText));
                        
                        // Re-enable button and restore original HTML on error
                        btn.prop('disabled', false);
                        btn.html(originalHtml);
                    }
                });
            });
        });
    </script>
@endsection
