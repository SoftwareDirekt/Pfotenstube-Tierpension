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
            <form action="{{route('admin.dogs.rooms.checkout-post')}}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-header">Hunde in den Zimmern</h5>
                    </div>
                    <div class="col-md-6 pt-2">
                        <div class="d-flex justify-content-end">
                            <div class="mx-3">
                                <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.dogs.in.rooms')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Search">
                            </div>
                            <div class="checkout">
                                <a href="{{route('admin.dogs.rooms.checkout')}}">
                                    <button type="submit" class="btn btn-primary">Kasse</button>
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <div class="table-responsive text-nowrap">
                  <table class="table" id="myTable">
                    <thead class="table-light">
                      <tr>
                        <th>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input mt-3 ms-2" id="selectAll">
                            </div>
                        </th>
                        <th>Zimmer Nummer</th>
                        <th>Hund ID</th>
                        <th>Hund Name</th>
                        <th>Kunde</th>
                        <th>Telefonnummer</th>
                        <th>Preisplan</th>
                        <th>Einchecken</th>
                        <th>Auschecken</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @if(count($reservations) > 0)
                    @foreach($reservations as $obj)
                        @php  if(!isset($obj->dog)){ continue; } @endphp 
                      <tr>
                        <td>
                            <div class="form-check">
                                <input type="checkbox" name="entry[]" value="{{$obj->id}}" class="form-check-input mt-1 ms-0 mycheck">
                            </div>
                        </td>
                        <td>{{$obj->room->number}}</td>
                        <td>{{$obj->dog->id}}</td>
                        <td>    
                            {{$obj->dog->name}}
                        </td>
                        <td>
                            <a href="{{route('admin.customers.preview', ['id' => $obj->dog->customer->id])}}">
                                {{$obj->dog->customer->name}} ({{$obj->dog->customer->id_number}})
                            </a>
                        </td>
                        <td>{{$obj->dog->customer->phone}}</td>
                        <td>@php echo isset($obj->plan->title) ? $obj->plan->title : '' @endphp</td>
                        <td>{{ date('d.m.Y', strtotime($obj->checkin_date)) }}</td>
                        <td>{{ date('d.m.Y', strtotime($obj->checkout_date)) }}</td>
                      </tr>
                    @endforeach
                    @else
                      <tr>
                        <td colspan="9" class="text-center">No records found</td>
                      </tr>
                    @endif
                    </tbody>
                  </table>
                </div>
            </form>
            
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
                        if(obj.dog)
                        {
                            html += '<tr>';
                                html += `<td><div class="form-check"><input type="checkbox" name="entry[]" value="${obj.id}" class="form-check-input mt-1 ms-0 mycheck"></div></td>`;
                                html += `<td>${obj.room.number}</td>`;
                                html += `<td>${obj.dog.id}</td>`;
                                html += `<td>${obj.dog.name}</td>`;
                                html += `<td><a href="/admin/customers/${obj.dog.customer.id}/preview">${obj.dog.customer.name} ${obj.dog.customer.id_number}</a></td>`;
                                html += `<td>${obj.dog.customer.phone}</td>`;
                                html += `<td>${obj.plan?.title ?? ''}</td>`;

                                var sp1 = obj.checkin_date.split(' ');
                                var date = sp1[0].split('-');
                                var checkin_date = date[2]+'.'+date[1]+'.'+date[0];

                                var sp2 = obj.checkout_date.split(' ');
                                var date2 = sp2[0].split('-');
                                var checkout_date = date2[2]+'.'+date2[1]+'.'+date2[0];
                                
                                
                                html += `<td>${checkin_date}</td>`;
                                html += `<td>${checkout_date}</td>`;
                            html += '</tr>';

                            $("#myTable tbody").html(html);
                        }
                    });
                }
                else{
                    let html = '<tr><td colspan="9" class="text-center">No record(s) found</td></tr>';
                    $("#myTable tbody").html(html);
                }
            }
        })
    }

    $("#selectAll").on('click', function(){
        if($("#selectAll").is(':checked'))
        {
            $(".mycheck").prop('checked', true);
        }else{
            $(".mycheck").prop('checked', false);
        }
    })

    $(window).on('load',function(){
        document.getElementById('togglerMenuBy').click();
    });
</script>
@endsection