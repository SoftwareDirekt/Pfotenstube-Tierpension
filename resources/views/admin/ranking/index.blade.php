@extends('admin.layouts.app')
@section('title')
    <title>Hunderanking</title>
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
                    <h5 class="card-header">Hunderanking</h5>
                </div>
                <div class="col-md-6 pt-2">
                    <div class="d-flex justify-content-end">
                        <div class="mx-2">
                            <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.dog.ranks')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Suche nach Titel">
                        </div>
                        <form action="{{route('admin.dog.ranks')}}" method="GET">
                            <div class="flex">
                                <select class="form-control mx-2" style="min-width: 150px" name="year">
                                    <option value="{{date('Y')}}" {{ (isset($_GET['year']) && $_GET['year'] == date('Y') ? 'selected' : '') }} >{{date('Y')}}</option>
                                    <option value="{{date('Y') - 1}}" {{ (isset($_GET['year']) && $_GET['year'] == date('Y') - 1 ? 'selected' : '') }}>{{date('Y') - 1}}</option>
                                    <option value="{{date('Y') - 2}}" {{ (isset($_GET['year']) && $_GET['year'] == date('Y') - 2 ? 'selected' : '') }}>{{date('Y') - 2}}</option>
                                    <option value="{{date('Y') - 3}}" {{ (isset($_GET['year']) && $_GET['year'] == date('Y') - 3 ? 'selected' : '') }}>{{date('Y') - 3}}</option>
                                    <option value="{{date('Y') - 4}}" {{ (isset($_GET['year']) && $_GET['year'] == date('Y') - 4 ? 'selected' : '') }}>{{date('Y') - 4}}</option>
                                </select>
                                <button class="btn btn-primary">Suche</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
              <table class="table" id="myTable">
                <thead class="table-light ">
                  <tr>
                    <th>Name</th>
                    <th>Kunde</th>
                    <th>Anzahl der Tage</th>
                    <th>Umsatz</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if(count($dogs) > 0)
                @foreach($dogs as $obj)
                    @php if(!isset($obj->dog)) continue; @endphp
                  <tr>
                    <td>
                        @if(isset($obj->dog->picture))
                        <img src="uploads/users/dogs/{{$obj->dog->picture}}" width="85" class="rounded" alt="Dog" />
                        @endif
                        {{$obj->dog->name}} ({{$obj->dog->id}})
                    </td>
                    <td>
                        <a href="{{route('admin.customers.preview',['id' => $obj->dog->customer->id])}}">
                            {{$obj->dog->customer->name}} ({{$obj->dog->customer->id_number}})
                        </a>
                    </td>
                    <td>{{abs($obj->total_stay_days)}}</td>
                    <td>&euro;{{ number_format($obj->cost, 2, '.','') }}</td>
                  </tr>
                @endforeach
                @else
                  <tr>
                    <td colspan="4" class="text-center">No records found</td>
                  </tr>
                @endif
                </tbody>
              </table>
            </div>
            <div class="pagination d-flex justify-content-end mt-5">
                {{$dogs->links()}}
            </div>
        </div>
    </div>
</div>

@endsection
@section('extra_js')

<script>
    async function ajaxSearch(route, method, token, id, keyword)
    {   
        var date = "{{ (isset($_GET['year'])) ? $_GET['year'] : date('Y') }}";

        $.ajax({
            url: route,
            method: method,
            data: {_token: token, keyword: keyword, date: date},
            success: function(res)
            {
                let html = '';
                if(res.length > 0)
                {
                    res.forEach((obj, index) => {
                        html += '<tr>';
                        html += `<td>`;
                        html += `<img src="uploads/users/dogs/${obj.dog.picture}" width="85" class="rounded" alt="Dog" />`;
                        html += `${obj.dog.name} (${obj.dog.id})`;
                        html += '</td>'
                        html += '<td>'
                        html += `<a href="/customers/${obj.dog.customer.id}/preview">`;
                        html += `${obj.dog.customer.name} (${obj.dog.customer.id_number})`;
                        html += '</a>';
                        html += '</td>';
                        html += `<td>${Math.abs(obj.total_stay_days)}</td>`;
                        html += `<td>&euro;${obj.cost}</td>`;
                        html += '</tr>';
                    });
                    $("#myTable tbody").html(html);
                }else{
                    html = '<tr><td colspan="4" class="text-center">No records found</td></tr>';

                    $("#myTable tbody").html(html);
                }
            }
        })
    }

</script>
@endsection