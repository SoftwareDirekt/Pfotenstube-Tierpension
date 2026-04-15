@extends('admin.layouts.app')
@section('title')
    <title>Zusatzkosten Management</title>
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
                    <h5 class="card-header">Zusatzkosten Management</h5>
                </div>
                <div class="col-md-6 pt-2">
                    <div class="d-flex justify-content-end">
                        <div class="mx-2">
                            <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.additional-costs')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Suche nach Titel, Preis">
                        </div>
                        <div>
                            <a href="{{route('admin.additional-cost.add')}}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> &nbsp; Neue Zusatzkosten
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
              <table class="table" id="myTable">
                <thead class="table-light">
                  <tr>
                    <th>Titel</th>
                    <th>Preis</th>
                    <th class="text-end">Aktion</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @foreach($costs as $cost)
                  <tr>
                    <td>@php echo($cost->title) @endphp</td>
                    <td>{{$cost->price}}&euro;</td>
                    <td>
                        <div class="d-flex justify-content-end">
                            <div>
                                <a href="{{route('admin.additional-cost.edit' , ['id' => $cost->id])}}">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </div>
                            <div class="mx-2">
                                <button class="no-style actionBtn" onclick="deleteCost({{$cost->id}})">
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
{{-- Delete Cost Modal --}}
<div class="modal fade" id="deleteCost" tabindex="  -1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Zusatzkosten loeschen</h3>
          </div>
          <p class="text-center">Moechten Sie diese Zusatzkosten wirklich loeschen?</p>
          <form class="row g-3" method="POST" action="{{route('admin.additional-cost.delete')}}">
            @csrf
            <div class="col-12 d-flex justify-content-center">
                <input type="hidden" class="form-control" name="id" id="id" />
                <button type="submit" class="btn btn-danger me-sm-3 me-1">Ja, loeschen</button>
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
                    res.forEach((obj, index) => {
                        html += '<tr>';
                        html += `<td>${obj.title}</td>`;
                        html += `<td>${obj.price}</td>`;
                        html += '<td><div class="d-flex justify-content-end">';
                        html += `<div><a href="/admin/additional-cost/${obj.id}/edit"><i class="fa fa-edit"></i></a></div>`;
                        html += `<div class="mx-2"><button class="no-style actionBtn" onclick="deleteCost(${obj.id})"><i class="fa fa-trash"></i></button></div>`;
                        html += '</div></td>';
                        html += '</tr>';
                    });
                    $("#myTable tbody").html(html);
                }else{
                    html = '<tr><td colspan="3" class="text-center">No records found</td></tr>';

                    $("#myTable tbody").html(html);
                }
            }
        })
    }

    function deleteCost(id)
    {
        $("#deleteCost #id").val(id);
        $("#deleteCost").modal('show');
    }   
</script>
@endsection
