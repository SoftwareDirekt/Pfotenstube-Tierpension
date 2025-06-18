@extends('admin.layouts.app')
@section('title')
    <title>Kunde</title>
@endsection
@section('extra_css')
<style>
.nested-table td,
th.iconz{
    min-width: 100px!important;
    max-width: 100px!important;
}
th.hundnam,
th.rasse,
td.hundnam,
td.rasse{
    min-width: 150px!important;
    max-width: 150px!important;
    word-wrap: break-word;
}
.main-table td:first-child,
.main-table th:first-child{
    width: 100px!important;
    max-width: 100px!important;
}
th.iconz.v,
td.iconz.v{
    min-width: 80px!important;
    max-width: 80px!important;
}
th.iconz.v.check,
td.iconz.v.check{
    min-width: 50px!important;
    max-width: 50px!important;
}
.tel{
 width: 180px!important;   
 max-width: 180px!important;   
}
</style>
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="card-header">Kunde management</h5>
                </div>
                <div class="col-md-6 pt-2">
                    <div class="d-flex justify-content-end">
                        <div class="mx-2">
                            <input type="text" class="form-control" autofocus onkeyup="ajaxSearch('{{route('admin.customers')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Suche nach ID-Nummer, Name, E-Mail oder Telefonnummer">
                        </div>
                        <div>
                            <a href="{{route('admin.customers.add')}}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> &nbsp; Neuer Kunde
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .nested-table td{
                    /* text-align: center */
                }
                /* .main-table th, .nested-table th, .nested-table td {
                    width: 100px!important;
                }
                .main-table th.rasse, .nested-table th.rasse, .nested-table td.rasse {
                    width: 100px!important;
                } */
            </style>

            <div class="table-responsive">
                <table class="table table-bordered main-table" id="myTable">
                    <thead class="table-light">
                    <tr>
                        <th class="id">
                            <button class="no-style btn-sort"
                            onclick="sort('{{route('admin.customers')}}', 'GET', '{{csrf_token()}}','myTable')">
                                <i class="fa fa-sort"></i>
                                ID
                            </button>
                        </th>
                        <th>Name</th>
                        <th class="hundnam">Hund</th>
                        <th class="rasse">Rasse</th>
                        <th class="iconz">K/NK</th>
                        <th class="iconz rasse">Tarif</th>
                        <th class="iconz v check">V</th>
                        <th class="iconz v">NB</th>
                        <th class="iconz v">NM</th>
                        <th class="iconz v">UV</th>
                        <th class="iconz v">S</th>
                        <th class="iconz">Wasser</th>
                        <th>Typ</th>
                        <th class="tel">Tel. Nummer</th>
                        <th>Aktionen</th>
                    </tr>
                    </thead>
                    <tbody>
                        @if(count($customers) > 0)
                        @foreach($customers as $obj)
                        <tr>
                            <td>{{$obj->id_number}}</td>
                            <td>{{$obj->name}}</td>
                            
                            <td colspan="10" class="p-0 m-0">
                                @if(count($obj->dogs) > 0)
                                <table class="table table-striped nested-table">
                                    <tbody>
                                        @foreach($obj->dogs as $item)
                                        @php
                                            $wl = $item->water_lover == 1 ? ' <span style="color: #5a5fe0; font-size: 17px" title="Wasserliebhaber"><i class="fa fa-tint"></i></span>' : '';
                                            $neutered = $item->neutered == 1 ? 'K' : 'NK';
                                        @endphp
                                        <tr>
                                            <td class="hundnam">{{$item->name}} ({{$item->id}})</td>
                                            <td class="rasse">{{$item->compatible_breed}}</td>
                                            <td>{{$neutered}}</td>
                                            <td class="rasse">
                                                @if(isset($item->day_plan_obj->price)) T{{$item->day_plan_obj->price}}/P{{$item->reg_plan_obj->price}} @endif
                                            </td>
                                            <td class="iconz v check"><?php echo $item->compatibility == 'V' ? '<i class="fa fa-check fs25"></i>' : '' ?></td>
                                            <td class="iconz v"><?php echo $item->compatibility == 'VJ' ? '<span class="tbspan nb">nB</span>' : '' ?></td>
                                            <td class="iconz v"><?php echo $item->compatibility == 'VM' ? '<span class="tbspan nm">nM</span>' : '' ?></td>
                                            <td class="iconz v"><?php echo $item->compatibility == 'UV' ? '<i class="fa fa-times text-danger fs-2"></i>' : '' ?></td>
                                            <td class="iconz v"><?php echo $item->compatibility == 'S' ? '<span class="tbspan s">S</span>' : '' ?></td>
                                            <td>{!! $wl !!}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{-- @else --}}
                                {{-- No dogs available --}}
                                @endif
                            </td>
                            <td>
                                @if($obj->type == 'Organisation')
                                Org
                                @else
                                Stamm
                                @endif
                            </td>
                            <td class="tel">{{$obj->phone}}</td>
                            <td>
                                <div class="d-flex justify-content-end">
                                    <div>
                                        <a href="{{route('admin.customers.edit', ['id' => $obj->id])}}">
                                            <i class="fa fa-edit md-icon"></i>
                                        </a>
                                    </div>
                                    <div class="mx-2">
                                        <a href="{{route('admin.customers.preview', ['id' => $obj->id])}}">
                                            <i class="fa fa-eye md-icon"></i>
                                        </a>
                                    </div>
                                    <div>
                                        <button class="no-style actionBtn" onclick="deleteCustomer('{{$obj->id}}')">
                                            <i class="fa fa-trash md-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>

                <div class="paginate">
                    {{$customers->links()}}
                </div>
            </div>

        </div>
    </div>
</div>
{{-- Delete Customer Modal --}}
<div class="modal fade" id="deleteCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Kunde löschen</h3>
          </div>
          <p class="text-center">Sind Sie sicher, dass Sie diesen Kunden löschen möchten?</p>
          <form class="row g-3" method="POST" action="{{route('admin.customers.delete')}}">
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

                        // var phone = '';
                        // if(obj.phone)
                        // {
                        //     phone = `(${obj.phone})`;   
                        // }

                        html += '<tr>';
                        html += `<td>${obj.id_number ? obj.id_number : ''}</td>`;
                        html += `<td>
                            ${obj.name}
                            </td>`;
                        html += '<td colspan="10" class="p-0 m-0">';
                            if(obj.dogs.length > 0)
                            {
                                html += `<table class="table table-striped nested-table"><tbody>`;
                                    obj.dogs.forEach(item => {
                                        var wl = item.water_lover == 1 ? ' <span style="color: #5a5fe0; font-size: 17px" title="Wasserliebhaber"><i class="fa fa-tint"></i></span>' : '';
                                        var neutered = item.neutered == 1 ? 'K' : 'NK';

                                        var V = '';
                                        var NB = '';
                                        var NM = '';
                                        var UV = '';
                                        var S = '';

                                        if(item.compatibility == 'V')
                                        {
                                            V = '<i class="fa fa-check fs25"></i>';
                                        }
                                        if(item.compatibility == 'VJ')
                                        {
                                            NB = '<span class="tbspan nb">nB</span>';
                                        }
                                        if(item.compatibility == 'VM')
                                        {
                                            NM = '<span class="tbspan nm">nM</span>';
                                        }
                                        if(item.compatibility == 'UV')
                                        {
                                            UV = '<i class="fa fa-times text-danger fs25"></i>';
                                        }
                                        if(item.compatibility == 'S')
                                        {
                                            S = '<span class="tbspan s">S</span>';
                                        }

                                        html += `<tr>`;
                                            html += `<td class="hundnam">${item.name} (${item.id})</td>`;
                                            html += `<td class="rasse">${item.compatible_breed}</td>`;
                                            html += `<td>${neutered}</td>`;
                                            html += `<td class="rasse">T${item.day_plan_obj && item.day_plan_obj.price ? item.day_plan_obj.price : 'N/A'}/P${item.reg_plan_obj && item.reg_plan_obj.price ? item.reg_plan_obj.price : 'N/A'}</td>`;
                                            html += `<td class="iconz v check">${V}</td>`;
                                            html += `<td class="iconz v">${NB}</td>`;
                                            html += `<td class="iconz v">${NM}</td>`;
                                            html += `<td class="iconz v">${UV}</td>`;
                                            html += `<td class="iconz v">${S}</td>`;
                                            html += `<td>${wl}</td>`;
                                        html += `</tr>`;

                                    })
                                html += `</tbody></table>`;
                            }
                        html += '</td>';
                        if(obj.type == 'Organisation')
                        {
                            html += `<td>Org</td>`;
                        }
                        else{

                            html += `<td>Stamm</td>`;
                        }
                        html += `<td>${obj.phone ? obj.phone : ''}</td>`;
                        html += '<td><div class="d-flex justify-content-end">';
                        html += `<div><a href="/admin/customers/${obj.id}/edit"><i class="fa fa-edit md-icon"></i></a></div>`;
                        html += `<div class="mx-2"><a href="admin/customers/${obj.id}/preview"><i class="fa fa-eye md-icon"></i></a></div>`;
                        html += `<div><button class="no-style actionBtn" onclick="deleteCustomer(${obj.id})"><i class="fa fa-trash md-icon"></i></button></div>`;
                        html += '</div></td>';
                        html += '</tr>';
                    });
                    $("#myTable tbody").html(html);
                }
                else{
                    html = '<tr><td colspan="7" class="text-center">No records found</td></tr>';

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

    function deleteCustomer(id)
    {
        $("#deleteCustomer #id").val(id);
        $("#deleteCustomer").modal('show');
    }
</script>
@endsection
