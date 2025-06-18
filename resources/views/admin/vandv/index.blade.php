@extends('admin.layouts.app')
@section('title')
    <title>Verstorben | Vermittelt</title>
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
                <div class="col-md-12 my-2">
                    <h5 class="card-header">Verstorben & Vermittelt Management</h5>
                </div>
                <hr>
                <div class="row d-flex justify-content-around mb-3">
                    <div class="col-md-5">
                        <div class="my-3">
                            <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.employees')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Search by chip number, pickup person, medication, health problems, eating habits">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="my-3">
                            <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.employees')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Search by name, type, email, phone number, city">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex justify-content-end">
                        <div class="form-floating form-floating-outline my-3">
                            <button class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row d-flex">
                <div class="col-md-12 mb-5">
                    <div class="table-responsive text-nowrap">
                        <div class="mx-2">
                            <h4 class="">Vermittelt</h4>
                        </div>
                      <table class="table" id="myTable">
                        <thead class="table-light">
                          <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Kunde</th>
                            <th>Alter</th>
                            <th>Vermitlungsdatum</th>
                            <th class="text-end">Aktionen</th>
                          </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach($dogs as $obj)
                            @if ($obj->status == 2)
                            <tr>
                                <td><img src="/uploads/users/dogs/{{$obj->picture}}" style="object-fit: cover" width="60" height="60" alt="img"></td>
                                <td>{{$obj->name}} ({{$obj->id}})</td>
                                <td><a href="{{route('admin.customers.preview' , ['id' => $obj->customer->id])}}">{{$obj->customer->name}} ({{$obj->customer->id_number}})</a></td>
                                @php 
                                $current_date = \Carbon\Carbon::now();
                                $birthdate = \Carbon\Carbon::createFromFormat('Y-m-d', $obj->age);
                                $age = $current_date->diffInDays($birthdate);
                                @endphp
                                <td>{{$age}}</td>
                                <td>{{isset($obj->adopt_date) ? date('d/m/Y', strtotime($obj->adopt_date)) : ''}}</td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <div class="mx-2">
                                            <button class="no-style actionBtn" onclick="deleteAdoptedDog('{{$obj->id}}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                      </table>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mx-2">
                        <h4 class="">Verstorben</h4>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table" id="myTable">
                          <thead class="table-light">
                            <tr>
                              <th></th>
                              <th>Name</th>
                              <th>Kunde</th>
                              <th>Alter</th>
                              <th>Sterbedatum</th>
                              <th class="text-end">Aktionen</th>
                            </tr>
                          </thead>
                          <tbody class="table-border-bottom-0">
                              @foreach($dogs as $obj)
                              @if ($obj->status == 3)
                              <tr>
                                  <td><img src="/uploads/users/dogs/{{$obj->picture}}" style="object-fit: cover" width="60" height="60" alt="img"></td>
                                  <td>{{$obj->name}} ({{$obj->id}})</td>
                                  <td><a href="{{route('admin.customers.preview' , ['id' => $obj->customer->id])}}">{{$obj->customer->name}} ({{$obj->customer->id_number}})</a></td>
                                  @php 
                                  $current_date = \Carbon\Carbon::now();
                                  $birthdate = \Carbon\Carbon::createFromFormat('Y-m-d', $obj->age);
                                  $age = $current_date->diffInDays($birthdate);
                                  @endphp
                                  <td>{{$age}}</td>
                                  <td>{{date('d/m/Y', strtotime($obj->died))}}</td>
                                  <td>
                                      <div class="d-flex justify-content-end">
                                          <div class="mx-2">
                                              <button class="no-style actionBtn" onclick="deleteDiedDog('{{$obj->id}}')">
                                                  <i class="fa fa-trash"></i>
                                              </button>
                                          </div>
                                      </div>
                                  </td>
                              </tr>
                              @endif
                              @endforeach
                          </tbody>
                        </table>
                      </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Died Dog Modal --}}
<div class="modal fade" id="deleteDiedDog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Hund löschen</h3>
          </div>
          <p class="text-center">Möchten Sie diesen Hund wirklich löschen?</p>
          <form class="row g-3" method="POST" action="{{route('admin.v_v.dieddog')}}">
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

{{-- Delete Adopted Dog Modal --}}
<div class="modal fade" id="deleteAdoptedDog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Hund löschen</h3>
          </div>
          <p class="text-center">Möchten Sie diesen Hund wirklich löschen?</p>
          <form class="row g-3" method="POST" action="{{route('admin.v_v.adopteddog')}}">
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

    function deleteDiedDog(id)
    {
        $("#deleteDiedDog #id").val(id);
        $("#deleteDiedDog").modal('show');
    }   

    function deleteAdoptedDog(id)
    {
        $("#deleteAdoptedDog #id").val(id);
        $("#deleteAdoptedDog").modal('show');
    }   
</script>
@endsection