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
                    <div class="col-md-12">
                        <div class="my-3">
                            <input type="text" class="form-control" id="vvSearchInput" onkeyup="searchVVDogs(this.value)" placeholder="Suche nach Name, Chipnummer, Kunde, Rasse, Gesundheitsprobleme, Medikamente, Allergien, Abholpersonen...">
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
                      <table class="table" id="adoptedTable">
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
                        <tbody class="table-border-bottom-0" id="adoptedTableBody">
                        @foreach($dogsVermittelt as $obj)
                            <tr>
                                <td><img src="/uploads/users/dogs/{{$obj->picture}}" style="object-fit: cover" width="60" height="60" alt="img"></td>
                                <td>{{$obj->name}} ({{$obj->id}})</td>
                                <td><a href="{{route('admin.customers.preview' , ['id' => $obj->customer->id])}}">{{$obj->customer->name}} ({{$obj->customer->id_number}})</a></td>
                                @if($obj->age)
                                @php
                                    $current_date = \Carbon\Carbon::now();
                                    $birthdate = \Carbon\Carbon::createFromFormat('Y-m-d', $obj->age);
                                    $age = $current_date->diffInYears($birthdate);
                                @endphp
                                    <td>{{$age}}</td>
                                @else
                                    <td>-</td>
                                @endif
                                <td>{{isset($obj->adopt_date) ? date('d.m.Y', strtotime($obj->adopt_date)) : ''}}</td>
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
                        <table class="table" id="diedTable">
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
                          <tbody class="table-border-bottom-0" id="diedTableBody">
                              @foreach($dogsVerstorben as $obj)
                              <tr>
                                  <td><img src="/uploads/users/dogs/{{$obj->picture}}" style="object-fit: cover" width="60" height="60" alt="img"></td>
                                  <td>{{$obj->name}} ({{$obj->id}})</td>
                                  <td><a href="{{route('admin.customers.preview' , ['id' => $obj->customer->id])}}">{{$obj->customer->name}} ({{$obj->customer->id_number}})</a></td>
                                  @if($obj->age)
                                      @php
                                          $current_date = \Carbon\Carbon::now();
                                          $birthdate = \Carbon\Carbon::createFromFormat('Y-m-d', $obj->age);
                                          $age = $current_date->diffInYears($birthdate);
                                      @endphp
                                      <td>{{$age}}</td>
                                  @else
                                      <td>-</td>
                                  @endif
                                  <td>{{date('d.m.Y', strtotime($obj->died))}}</td>
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
    function calculateAge(birthdate) {
        if (!birthdate) return '-';
        const currentDate = new Date();
        const birth = new Date(birthdate);
        const age = currentDate.getFullYear() - birth.getFullYear();
        const monthDiff = currentDate.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && currentDate.getDate() < birth.getDate())) {
            return age - 1;
        }
        return age;
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }

    function searchVVDogs(keyword) {
        if (!keyword || keyword.trim() === '') {
            location.reload();
            return;
        }

        $.ajax({
            url: '{{ route("admin.v_v.search") }}',
            method: 'GET',
            data: {
                _token: '{{ csrf_token() }}',
                keyword: keyword,
                type: 'both'
            },
            success: function(res) {
                let adoptedHtml = '';
                let diedHtml = '';

                if(res.length > 0) {
                    res.forEach(obj => {
                        if (!obj.customer) return;

                        const picture = obj.picture ? `/uploads/users/dogs/${obj.picture}` : '/uploads/users/dogs/no-user-picture.gif';
                        const age = calculateAge(obj.age);
                        const customerLink = `/admin/customers/${obj.customer.id}/preview`;
                        const customerName = `${obj.customer.name}${obj.customer.id_number ? ' (' + obj.customer.id_number + ')' : ''}`;

                        const rowHtml = `
                            <tr>
                                <td><img src="${picture}" style="object-fit: cover" width="60" height="60" alt="img"></td>
                                <td>${obj.name} (${obj.id})</td>
                                <td><a href="${customerLink}">${customerName}</a></td>
                                <td>${age}</td>
                                <td>${obj.status == 2 ? formatDate(obj.adopt_date) : formatDate(obj.died)}</td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <div class="mx-2">
                                            <button class="no-style actionBtn" onclick="${obj.status == 2 ? 'deleteAdoptedDog' : 'deleteDiedDog'}('${obj.id}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;

                        if (obj.status == 2) {
                            adoptedHtml += rowHtml;
                        } else if (obj.status == 3) {
                            diedHtml += rowHtml;
                        }
                    });
                }

                $("#adoptedTableBody").html(adoptedHtml || '<tr><td colspan="6" class="text-center">Keine Ergebnisse gefunden</td></tr>');
                $("#diedTableBody").html(diedHtml || '<tr><td colspan="6" class="text-center">Keine Ergebnisse gefunden</td></tr>');
            },
            error: function(xhr) {
                console.error('Search error:', xhr);
            }
        });
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
