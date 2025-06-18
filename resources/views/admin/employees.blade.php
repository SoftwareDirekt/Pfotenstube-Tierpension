@extends('admin.layouts.app')
@section('title')
    <title>Mitarbeiter</title>
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
                <div class="col-md-6">
                    <h5 class="card-header">Mitarbeiter</h5>
                </div>
                <div class="col-md-6 pt-2">
                    <div class="d-flex justify-content-end">
                        <div class="mx-2">
                            <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.employees')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Suche nach Name, E-Mail oder Telefonnummer">
                        </div>
                        <div>
                            <a href="{{route('admin.employees.add')}}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> &nbsp; Neuer Mitarbeiter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
              <table class="table" id="myTable">
                <thead class="table-light">
                  <tr>
                    <th></th>
                    <th>
                        <button class="no-style btn-sort" 
                            onclick="sort('{{route('admin.employees')}}', 'GET', '{{csrf_token()}}','myTable')">
                            <i class="fa fa-sort"></i>
                            ID
                        </button>
                    </th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Nutzername</th>
                    <th>Telefon</th>
                    <th>Adresse</th>
                    <th>Stadt</th>
                    <th>Land</th>
                    <th>Status</th>
                    <th class="text-end">Aktionen</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @foreach($users as $obj)
                  <tr>
                    <td>
                    @if ($obj->picture)
                        <img src="uploads/users/{{$obj->picture}}" width="50" height="50" style="object-fit: cover" alt="#">   
                    @endif
                    </td>
                    <td>{{$obj->id}}</td>
                    <td>{{$obj->name}}</td>
                    <td>{{$obj->email}}</td>
                    <td>{{$obj->username}}</td>
                    <td>{{$obj->phone}}</td>
                    <td>{{$obj->address}}</td>
                    <td>{{$obj->city}}</td>
                    <td><?php echo $obj->country; ?></td>
                    <td>
                        @if($obj->status == 1)
                        <span class="badge bg-label-primary me-1">Aktiv</span>
                        @else
                        <span class="badge bg-label-secondary me-1">Inaktiv</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex justify-content-end">
                            <div>
                                <a href="{{route('admin.employees.edit', ['id' => $obj->id])}}">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </div>
                            <div class="mx-2">
                                <button class="no-style actionBtn" onclick="deleteEmployee('{{$obj->id}}')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
        </div>
    </div>
</div>
{{-- Delete Employee Modal --}}
<div class="modal fade" id="deleteEMPLOYEE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Mitarbeiter löschen</h3>
          </div>
          <p class="text-center">Sind Sie sicher, dass Sie diesen Mitarbeiter löschen möchten?</p>
          <form class="row g-3" method="POST" action="{{route('admin.employees.delete')}}">
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
    var order = 'asc';
    async function ajaxSearch(route, method, token, id, keyword='')
    {   

        $.ajax({
            url: route,
            method: method,
            data: {_token: token, keyword: keyword, order: order},
            success: function(res)
            {
                let html = '';
                if(res.length > 0)
                {
                    res.forEach(obj => {
                        html += '<tr>';
                        if(obj.picture != null)
                        {
                            html += `<td><img src="/uploads/users/${obj.picture}" alt="Employee Picture" width="50" height="50" style="object-fit: cover"></td>`;
                        }
                        else{
                            html += '<td></td>';
                        }
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
                        html += `<div><a href="/admin/employees/${obj.id}/edit"><i class="fa fa-edit"></i></a></div>`;
                        html += `<div class='mx-2'><button class="no-style actionBtn" onclick="deleteEmployee(${obj.id})"><i class="fa fa-trash"></i></button></div>`;
                        html += '</div></td>';
                        html += '</tr>';
                    });
                    $("#myTable tbody").html(html);
                }else{
                    html = '<tr><td colspan="15" class="text-center">No records found</td></tr>';

                    $("#myTable tbody").html(html);
                }
            }
        })
    }

    async function sort(route, method, token, id)
    {
        await ajaxSearch(route, method, token, id);
        order = (order == 'asc') ? 'desc' : 'asc';
    }

    function deleteEmployee(id)
    {
        $("#deleteEMPLOYEE #id").val(id);
        $("#deleteEMPLOYEE").modal('show');
    }   
</script>
@endsection