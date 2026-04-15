@extends('admin.layouts.app')
@section('title')
    <title>Zimmer Management</title>
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
                    <h5 class="card-header">Zimmer Management</h5>
                </div>
                <div class="col-md-6 pt-2">
                    <div class="d-flex justify-content-end">
                        <div class="mx-2">
                            <input type="text" class="form-control" autofocus onkeyup="ajaxSearch('{{route('admin.rooms')}}', 'GET', '{{csrf_token()}}','myTable', this.value)" placeholder="Suche nach Nummer, Typ">
                        </div>
                        <div class="mx-2">
                            <a href="{{ route('admin.rooms.export') }}" class="btn btn-success">
                                <i class="fa fa-file-excel"></i> &nbsp; Export Excel
                            </a>
                        </div>
                        <div>
                            <a href="{{route('admin.rooms.add')}}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> &nbsp; Neuer Zimmer
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
                            onclick="sort('{{route('admin.rooms')}}', 'GET', '{{csrf_token()}}','myTable')">
                            <i class="fa fa-sort"></i>
                            Nummer
                        </button>
                    </th>
                    <th>Typ</th>
                    <th>Kapazität</th>
                    <th>Befehl</th>
                    <th>Status</th>
                    <th class="text-end">Aktionen</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0" id="sortable">
                @foreach($rooms as $obj)
                  <tr data-id="{{ $obj->id }}">
                    <td>
                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M278.6 9.4c-12.5-12.5-32.8-12.5-45.3 0l-64 64c-9.2 9.2-11.9 22.9-6.9 34.9s16.6 19.8 29.6 19.8h32v96H128V192c0-12.9-7.8-24.6-19.8-29.6s-25.7-2.2-34.9 6.9l-64 64c-12.5 12.5-12.5 32.8 0 45.3l64 64c9.2 9.2 22.9 11.9 34.9 6.9s19.8-16.6 19.8-29.6V288h96v96H192c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l64 64c12.5 12.5 32.8 12.5 45.3 0l64-64c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8H288V288h96v32c0 12.9 7.8 24.6 19.8 29.6s25.7 2.2 34.9-6.9l64-64c12.5-12.5 12.5-32.8 0-45.3l-64-64c-9.2-9.2-22.9-11.9-34.9-6.9s-19.8 16.6-19.8 29.6v32H288V128h32c12.9 0 24.6-7.8 29.6-19.8s2.2-25.7-6.9-34.9l-64-64z"/></svg>
                    </td>
                    <td>{{$obj->number}}</td>
                    <td>{{$obj->type}}</td>
                    <td>{{$obj->capacity}}</td>
                    <td>{{$obj->order}}</td>
                    <td>
                        @if($obj->status == 1)
                        <span class="badge bg-label-primary me-1">Active</span>
                        @else
                        <span class="badge bg-label-secondary me-1">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex justify-content-end">
                            <div>
                                <a href="{{route('admin.rooms.edit',['id' => $obj->id])}}">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </div>
                            <div class="mx-2">
                                <button class="no-style actionBtn" onclick="deleteRoom({{$obj->id}})">
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
{{-- Delete Room Modal --}}
<div class="modal fade" id="deleteRoom" tabindex="  -1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Raum löschen</h3>
          </div>
          <p class="text-center">Möchten Sie diesen Raum wirklich löschen?</p>
          <form class="row g-3" method="POST" action="{{route('admin.rooms.delete')}}">
            @csrf
            <div class="col-12 d-flex justify-content-center">
                <input type="hidden" class="form-control" name="id" id="id" />
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
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
    var order = 'desc';
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
                        html += `<tr data-id="${obj.id}">`;
                        html += `<td><svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M278.6 9.4c-12.5-12.5-32.8-12.5-45.3 0l-64 64c-9.2 9.2-11.9 22.9-6.9 34.9s16.6 19.8 29.6 19.8h32v96H128V192c0-12.9-7.8-24.6-19.8-29.6s-25.7-2.2-34.9 6.9l-64 64c-12.5 12.5-12.5 32.8 0 45.3l64 64c9.2 9.2 22.9 11.9 34.9 6.9s19.8-16.6 19.8-29.6V288h96v96H192c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l64 64c12.5 12.5 32.8 12.5 45.3 0l64-64c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8H288V288h96v32c0 12.9 7.8 24.6 19.8 29.6s25.7 2.2 34.9-6.9l64-64c12.5-12.5 12.5-32.8 0-45.3l-64-64c-9.2-9.2-22.9-11.9-34.9-6.9s-19.8 16.6-19.8 29.6v32H288V128h32c12.9 0 24.6-7.8 29.6-19.8s2.2-25.7-6.9-34.9l-64-64z"/></svg></td>`;
                        html += `<td>${obj.number}</td>`;
                        html += `<td>${obj.type}</td>`;
                        html += `<td>${obj.capacity}</td>`;
                        html += `<td>${(obj.order != null) ? obj.order: ''}</td>`;
                        if(obj.status == 1)
                        {
                            html += '<td><span class="badge bg-label-primary me-1">Active</span></td>'
                        }else{
                            html += '<td><span class="badge bg-label-secondary me-1">Inactive</span></td>'
                        }
                        html += '<td><div class="d-flex justify-content-end">';
                        html += `<div><a href="/admin/rooms/${obj.id}/edit"><i class="fa fa-edit"></i></a></div>`;
                        html += `<div class="mx-2"><button class="no-style actionBtn" onclick="deleteRoom(${obj.id})"><i class="fa fa-trash"></i></button></div>`;
                        html += '</div></td>';
                        html += '</tr>';
                    });
                    $("#myTable tbody").html(html);
                }
                else{
                    html = '<tr><td colspan="6" class="text-center">No records found</td></tr>';

                    $("#myTable tbody").html(html);
                }
            }
        })
    }

    async function sort(route, method, token, id)
    {
        await ajaxSearch(route, method, token, id);
        order = (order == 'desc') ? 'asc' : 'desc';
    }

    function deleteRoom(id)
    {
        $("#deleteRoom #id").val(id);
        $("#deleteRoom").modal('show');
    }   

    $(function() {
        $("#sortable").sortable({
            update: function(event, ui) {
                let order = $(this).sortable('toArray', { attribute: 'data-id' });
                updateOrder(order);
            }
        });
    });

    function updateOrder(order)
    {
        $.ajax({
            url: "{{route('admin.rooms.order.update')}}",
            method: 'POST',
            data: {
                order: order,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log(response)
                console.log('Order updated successfully');
            },
            error: function(error) {
                console.log('Error updating order');
            }
        });
    }

</script>
@endsection