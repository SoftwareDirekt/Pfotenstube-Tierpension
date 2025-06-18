@extends('admin.layouts.app')
@section('title')
    <title>Zahlung</title>
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
                    <h5 class="card-header">Zahlung Management</h5>
                </div>
                <hr>
                <form class="row">
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline my-3">
                            @php
                            if (!isset($_GET['month'])){
                                $_GET['month'] = 'all';
                            }
                            @endphp
                            <select name="month" class="form-control">
                                @foreach(range(1, 12) as $month)
                                    <option value="{{ $month }}" {{isset($_GET['month']) && $_GET['month'] == $month ? 'selected' : ''}}>{{ __('months.' . date("F", mktime(0, 0, 0, $month, 10))) }}</option>
                                @endforeach
                                <option value="all" {{isset($_GET['month']) && $_GET['month'] == 'all' ? 'selected' : ''}}>Alle</option>
                            </select>
                            <label for="type">Monat</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating form-floating-outline my-3">
                            @php
                                if (!isset($_GET['year'])){
                                    $_GET['year'] = 'all';
                                }
                            @endphp
                            <select name="year" class="form-control">
                                @foreach(range(date('Y'), 2020) as $year)
                                    <option value="{{ $year }}" {{isset($_GET['year']) && $_GET['year'] == $year ? 'selected' : ''}}>{{ $year }}</option>
                                @endforeach
                                    <option value="all" {{isset($_GET['year']) && $_GET['year'] == 'all' ? 'selected' : ''}}>Alle</option>
                            </select>
                            <label for="type">Jahr</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating form-floating-outline my-4">
                            <button class="btn btn-primary">Search</button>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end">
                        <div class="form-floating form-floating-outline">
                            <form action="{{route('admin.payment')}}" method="GET" id="statusForm">
                                <select name="status" id="statusChoos" class="form-control mt-4" onchange="submitStatus(this.value)" style="width: 300px;border:2px solid blue;">
                                    <option value="alle" {{ isset($_GET['st']) && $_GET['st'] == 'alle' ? 'selected': '' }}>Alle</option>
                                    <option value="2" {{ isset($_GET['st']) && $_GET['st'] == 2 ? 'selected': '' }}>Bezahlt</option>
                                    <option value="0" {{ isset($_GET['st']) && $_GET['st'] == 0 ? 'selected': '' }}>Nicht bezahlt</option>
                                    <option value="1" {{ isset($_GET['st']) && $_GET['st'] == 1 ? 'selected': '' }}>Offen</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap">
              <table class="table" id="myTable">
                <thead class="table-light">
                  <tr>
                    <th>Reg #</th>
                    <th>Hund</th>
                    <th>Kunde ID</th>
                    <th>Kunde</th>
                    <th>Einchecken</th>
                    <th>Auschecken</th>
                    <th>Art</th>
                    <th>Preis</th>
                    <th>Rabatt</th>
                    <th>R.Betrag</th>
                    <th>Erhalten</th>
                    <th>K. Saldo</th>
                    <th>Status</th>
                    <th class="text-end">Aktionen</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if(count($payments) > 0)
                @foreach($payments as $obj)
                    @php 
                        if(!isset($obj->reservation->dog) || $obj->reservation->dog == null)
                        {
                            continue;
                        }
                    @endphp
                  <tr>
                    <td>{{$obj->id}}</td>
                    <td>{{$obj->reservation->dog->name}}</td>
                    <td>{{$obj->reservation->dog->customer->id_number}}</td>
                    <td>
                        <a href="{{route('admin.customers.preview', ['id'=> $obj->reservation->dog->customer_id])}}">
                            {{$obj->reservation->dog->customer->name}}
                        </a>
                    </td>
                    <td>{{date('d.m.Y', strtotime($obj->reservation->checkin_date))}}</td>
                    <td>{{date('d.m.Y', strtotime($obj->reservation->checkout_date))}}</td>
                    <td>{{$obj->type}}</td>
                    <td>&euro;{{ abs($obj->cost) }}</td>
                    <td>&euro;{{ abs($obj->discount) }}</td>
                    <td>&euro;{{ abs($obj->cost) }}</td>
                    <td>&euro;{{ abs($obj->received_amount) }}</td>
                    <td>
                        @if($obj->remaining_amount < 0)
                            <span class="text-danger">{{$obj->remaining_amount}}&euro;</span>
                        @elseif($obj->remaining_amount > 0)
                            <span class="text-success">{{$obj->remaining_amount}}&euro;</span>
                        @else
                            {{$obj->remaining_amount}}&euro;
                        @endif
                    </td>
                    <td>
                        @if($obj->status == 0)
                        <span class="badge bg-label-secondary me-1">Nicht bezahlt</span>
                        @elseif($obj->status == 2)
                        <span class="badge bg-label-success me-1">Bezahlt</span>
                        @elseif($obj->status == 1)
                        <span class="badge bg-label-info me-1">Offen</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex justify-content-end">
                            <div>
                                <button class="no-style actionBtn" onclick="editPayment('{{$obj->id}}', '{{$obj->type}}', '{{abs($obj->cost)}}', '{{abs($obj->received_amount)}}', '{{$obj->discount}}', '{{$obj->discount_amount}}', '{{$obj->status}}', '{{$obj->remaining_amount}}')">
                                    <i class="fa fa-edit"></i>
                                </button>
                            </div>
                            <div class="mx-2">
                                <button class="no-style actionBtn" onclick="deletePayment('{{$obj->id}}')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                  </tr>
                @endforeach
                @else
                    {{-- <tr>
                        <td colspan="14">No records found</td>
                    </tr> --}}
                    <tr>
                        <td>Keine Aufzeichnungen gefunden</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endif
                </tbody>
              </table>
            </div>
            <div class="paginate">
                {{$payments->links()}}
            </div>
        </div>
    </div>
</div>

{{-- Edit Payment Modal --}}
<div class="modal fade" id="editPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
        <div class="modal-content p-3 p-md-0">
            <div class="modal-body p-md-0">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="card-header text-center">
                        <h5 class="mb-4">Aktualisieren Zahlung</h5>
                    </div>
                    <div class="row">
                        <div class="mb-4">
                            <div class="card-body">
                              <form action="{{route('admin.payment.update')}}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline mb-4">
                                            <select name="type" id="type" class="form-control" required>
                                                <option value="Bar">Bar</option>
                                                <option value="Banküberweisung">Banküberweisung</option>
                                            </select>
                                            <label for="type">Zahlungsart</label>
                                        </div>
                                        @error('type')
                                            <p class="formError">*{{$message}}</p>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline mb-4">
                                            <input type="number" class="form-control" name="cost" id="cost" value="" placeholder="Kosten" required />
                                            <label for="cost">Rechnungsbetrag</label>
                                        </div>
                                        @error('cost')
                                            <p class="formError">*{{$message}}</p>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline mb-4">
                                            <input type="number" class="form-control" name="received_amount" id="received_amount" value="" placeholder="Erhaltenen Betrag" required />
                                            <label for="received_amount">Betrag bis dato erhalten</label>
                                        </div>
                                        @error('received_amount')
                                            <p class="formError">*{{$message}}</p>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline mb-4">
                                            <select name="status" id="status" class="form-control" required>
                                                <option value="2">Bezahlt</option>
                                                <option value="0">Unbezahlt</option>
                                                <option value="1">Offen</option>
                                            </select>
                                            <label for="Status">Status</label>
                                        </div>
                                    </div>

                                    <div class="col-md-12 d-flex justify-content-end">
                                        <p id="saldo"></p>
                                    </div>
                                    {{-- <div class="col-md-6">
                                        <div class="form-floating form-floating-outline mb-4">
                                            <input type="number" class="form-control" name="discount" id="discount" value="" placeholder="Rabatt" required />
                                            <label for="discount">Rabatt</label>
                                        </div>
                                        @error('discount')
                                            <p class="formError">*{{$message}}</p>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline mb-4">
                                            <input type="number" class="form-control" name="discount_amount" id="discount_amount" value="" placeholder="Rabatt Betrag" required />
                                            <label for="discount_amount">Rabatt Betrag</label>
                                        </div>
                                        @error('discount_amount')
                                            <p class="formError">*{{$message}}</p>
                                        @enderror
                                    </div>
                                     --}}
                                </div>
                                <input type="hidden" class="form-control" name="id" id="id">
                                <button type="submit" class="btn btn-primary">Speichern</button>
                                <button
                                    type="reset"
                                    class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal"
                                    aria-label="Close">
                                    Schließen
                                </button>
                              </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Payment Modal --}}
<div class="modal fade" id="deletePayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Zahlung löschen</h3>
          </div>
          <p class="text-center">Möchten Sie diese Zahlung wirklich löschen?</p>
          <form class="row g-3" method="POST" action="{{route('admin.payment.delete')}}">
            @csrf
            <div class="col-12 d-flex justify-content-center">
                <input type="hidden" name="id" id="id" />
                <button type="submit" class="btn btn-danger me-sm-3 me-1">Ja, löschen</button>
                <button
                    type="reset"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                    Stornieren
                </button>
            </div>
          </form>
        </div>
      </div>
    </div>
</div>

@endsection
@section('extra_js')

<script>

    $(window).on('load',function(){
        document.getElementById('togglerMenuBy').click();
    });

    async function ajaxSearch(route, method, token, id, keyword)
    {
        $.ajax({
            url: route,
            method: method,
            data: {_token: token, keyword: keyword},
            success: function(res)
            {
                let html = '';
                if(res.length > 0)
                {
                    res.forEach(obj => {
                        html += '<tr>';
                        html += `<td>${obj.id}</td>`;
                        html += `<td>${obj.name}</td>`;
                        html += `<td>${obj.email}</td>`;
                        html += `<td>${obj.username}</td>`;
                        html += `<td>${(obj.phone != null) ? obj.phone: ''}</td>`;
                        html += `<td>${(obj.address != null) ? obj.address: ''}</td>`;
                        html += `<td>${(obj.city != null) ? obj.city: ''}</td>`;
                        html += `<td>${ (obj.country != null) ? obj.country: '' }</td>`;
                        if(obj.status == 1)
                        {
                            html += '<td><span class="badge bg-label-primary me-1">Active</span></td>'
                        }else{
                            html += '<td><span class="badge bg-label-secondary me-1">Inactive</span></td>'
                        }
                        html += '<td><div class="d-flex justify-content-end">';
                        html += '<div><a href="#"><i class="fa fa-edit"></i></a></div>';
                        html += '<div class="mx-2"><button class="no-style actionBtn"><i class="fa fa-trash"></i></button></div>';
                        html += '</div></td>';
                        html += '</tr>';
                    });
                    $("#myTable tbody").html(html);
                }
            }
        })
    }

    function editPayment(id, type, cost, received_amount, discount, discount_amount, status, remaining_amount)
    {

        $("#editPayment #id").val(id);
        $("#editPayment #type").val(type);
        $("#editPayment #cost").val(cost);
        $("#editPayment #received_amount").val(received_amount);
        $("#editPayment #saldo").html(remaining_amount+'&euro;');
        
        if(remaining_amount > 0)
        {
            $("#editPayment #saldo").addClass('text-success');
            $("#editPayment #saldo").removeClass('text-danger');
        }else if(remaining_amount < 0)
        {
            $("#editPayment #saldo").addClass('text-danger');
            $("#editPayment #saldo").removeClass('text-success');
        }
        else{
            $("#editPayment #saldo").removeClass('text-success');
            $("#editPayment #saldo").removeClass('text-danger');
        }
        
        // $("#editPayment #discount").val(discount);
        // $("#editPayment #discount_amount").val(discount_amount);
        // $("#editPayment #status").val(status);
        
        $("#editPayment").modal('show');
    }

    function deletePayment(id)
    {
        $("#deletePayment #id").val(id);
        $("#deletePayment").modal('show');
    }
</script>

<script>
    $(document).ready(function() {
        $('#myTable').DataTable({
            "oLanguage": {
                "sSearch": "Suche: ",
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

    function submitStatus(val)
    {
        window.location.href="/admin/payment?st="+val;
    }

</script>
@endsection
