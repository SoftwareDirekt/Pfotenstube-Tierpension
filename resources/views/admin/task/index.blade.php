@extends('admin.layouts.app')
@section('title')
    <title>Aufgaben Management</title>
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
                    <h5 class="card-header">Aufgaben Management</h5>
                </div>
                <div class="col-md-6 pt-2">
                    <div class="d-flex justify-content-end">
                        <div class="mx-2">
                            <input type="text" class="form-control" onkeyup="ajaxSearch('{{route('admin.tasks')}}', 'GET', '{{csrf_token()}}', 'myTable', this.value)" placeholder="Suche nach Titel">
                        </div>
                        <div>
                            <a href="javascript:void()" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTask">
                                <i class="fa fa-plus"></i> &nbsp; Neuer Aufgabe
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
              <table class="table" id="myTable">
                <thead class="table-light ">
                  <tr>
                    <th>#</th>
                    <th>Titel</th>
                    <td>Tage</td>
                    <th class="text-end">Aktionen</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @foreach($task as $obj)
                  @php  
                    $days = json_decode($obj->days, true);
                  @endphp
                  <tr>
                    <td>{{$loop->index + 1}}</td>
                    <td>{{$obj->title}}</td>
                    <td>
                      @if(is_array($days) && count($days) > 0)
                      <ul class="daysList">
                      @foreach($days as $day)
                        @switch($day)
                            @case('Monday')
                              <li>Montag</li>
                              @break
                            @case('Tuesday')
                              <li>Dienstag</li>
                              @break
                            @case('Wednesday')
                              <li>Mittwoch</li>
                              @break
                            @case('Thursday')
                              <li>Donnerstag</li>
                              @break
                            @case('Friday')
                              <li>Freitag</li>
                              @break
                            @case('Saturday')
                              <li>Samstag</li>
                              @break
                            @case('Sunday')
                              <li>Sonntag</li>
                              @break
                            @default
                        @endswitch
                      @endforeach
                      </ul>
                      @endif
                    </td>
                    <td>
                        <div class="d-flex justify-content-end">
                            <div>
                                <button class="no-style actionBtn" onclick="updateTask('{{$obj->id}}' , '{{$obj->title}}', '{{$obj->days}}')">
                                    <i class="fa fa-edit"></i>
                                </button>
                            </div>
                            <div>
                                <button class="no-style actionBtn" onclick="deleteTask('{{$obj->id}}')">
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

{{-- Add Task Modal --}}
<div class="modal fade" id="addTask" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1 text-start">Neue Aufgabe hinzufügen</h3>
          </div>
          <form class="row g-3" method="POST" action="{{route('admin.task.add')}}">
            @csrf
            <div class="col-md-12">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" name="title" id="title" placeholder="Titel" />
                    <label for="title">Titel</label>
                </div>
            </div>
            <h5 class="p-0 m-0">Wählen Sie Tage aus</h5>
            <div class="col-md-12 d-flex">
              <div class="form-check mb-2">
                  <input type="checkbox" class="form-check-input" name="days[]" id="monday" placeholder="Montag" value="Monday" />
                  <label for="monday">Montag</label>
              </div>
              <div class="form-check mb-2 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="tuesday" placeholder="Tuesday" value="Tuesday" />
                <label for="tuesday">Dienstag</label>
              </div>
              <div class="form-check mb-2 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="wednesday" placeholder="Wednesday" value="Wednesday" />
                <label for="wednesday">Mittwoch</label>
              </div>
              <div class="form-check mb-2 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="thursday" placeholder="Thursday" value="Thursday" />
                <label for="thursday">Donnerstag</label>
              </div>
            </div>
            <div class="col-md-12 d-flex">
              <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="friday" placeholder="Friday" value="Friday" />
                <label for="friday">Freitag</label>
              </div>
              <div class="form-check mb-4 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="saturday" placeholder="Saturday" value="Saturday" />
                <label for="saturday">Samstag</label>
              </div>
              <div class="form-check mb-4 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="sunday" placeholder="Sunday" value="Sunday" />
                <label for="sunday">Sonntag</label>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Speichern</button>
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

{{-- Update Task Modal --}}
<div class="modal fade" id="updateTask" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1 text-start">Update-Aufgabe</h3>
          </div>
          <form class="row g-3" method="POST" action="{{route('admin.task.update')}}">
            @csrf
            <div class="col-md-12">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" name="title" id="title" placeholder="Titel" />
                    <label for="title">Titel</label>
                </div>
            </div>
            <h5 class="p-0 m-0">Wählen Sie Tage aus</h5>
            <div class="col-md-12 d-flex">
              <div class="form-check mb-2">
                  <input type="checkbox" class="form-check-input" name="days[]" id="monday" placeholder="Montag" value="Monday" />
                  <label for="monday">Montag</label>
              </div>
              <div class="form-check mb-2 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="tuesday" placeholder="Tuesday" value="Tuesday" />
                <label for="tuesday">Dienstag</label>
              </div>
              <div class="form-check mb-2 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="wednesday" placeholder="Wednesday" value="Wednesday" />
                <label for="wednesday">Mittwoch</label>
              </div>
              <div class="form-check mb-2 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="thursday" placeholder="Thursday" value="Thursday" />
                <label for="thursday">Donnerstag</label>
              </div>
            </div>
            <div class="col-md-12 d-flex">
              <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="friday" placeholder="Friday" value="Friday" />
                <label for="friday">Freitag</label>
              </div>
              <div class="form-check mb-4 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="saturday" placeholder="Saturday" value="Saturday" />
                <label for="saturday">Samstag</label>
              </div>
              <div class="form-check mb-4 ms-4">
                <input type="checkbox" class="form-check-input" name="days[]" id="sunday" placeholder="Sunday" value="Sunday" />
                <label for="sunday">Sonntag</label>
              </div>
            </div>
            <input type="hidden" class="form-control" name="id" id="id">
            <div class="col-12">
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Speichern</button>
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

{{-- Delete Task Modal --}}
<div class="modal fade" id="deleteTask" tabindex="  -1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Aufgabe löschen</h3>
          </div>
          <p class="text-center">Sind Sie sicher, dass Sie diese Aufgabe löschen möchten?</p>
          <form class="row g-3" method="POST" action="{{route('admin.task.delete')}}">
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
                        html += `<td>${index + 1}</td>`;
                        html += `<td>${obj.title}</td>`;
                        var days_html = "";
                        if(obj.days)
                        {
                          var days_array = [];
                          var days = JSON.parse(obj.days);
                          if(days.length > 0)
                          {
                            days_html += "<ul class='daysList'>";
                            
                            days.forEach(day => {
                              days_array.push(day);
                              if(day == 'Monday'){
                                days_html += "<li>Montag</li>";
                              }
                              else if( day == 'Tuesday')
                              {
                                days_html += "<li>Dienstag</li>";
                              }
                              else if( day == 'Wednesday')
                              {
                                days_html += "<li>Mittwoch</li>";
                              }
                              else if( day == 'Thursday')
                              {
                                days_html += "<li>Donnerstag</li>";
                              }
                              else if( day == 'Friday')
                              {
                                days_html += "<li>Freitag</li>";
                              }
                              else if( day == 'Saturday')
                              {
                                days_html += "<li>Samstag</li>";
                              }
                              else if( day == 'Sunday')
                              {
                                days_html += "<li>Sonntag</li>";
                              } 
                            });
                            days_html += "</ul>";
                          }
                        }
                        else{
                          var days_array = null;
                        }
                        html += `<td>${days_html}</td>`;
                        html += '<td><div class="d-flex justify-content-end">';
                        html += `<div><button class="no-style actionBtn" onclick="updateTask(${obj.id}, '${obj.title}', '${JSON.stringify(days_array)}')"><i class="fa fa-edit"></i></button></div>`;
                        html += `<div><button class="no-style actionBtn" onclick="deleteTask(${obj.id})"><i class="fa fa-trash"></i></button></div>`;
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

    function updateTask(id,title, days)
    {
      console.log(days);
        var days_html = "";
        
        $("#updateTask input[type='checkbox']").attr('checked', false);

        if(days != null && days.length > 0)
        {
          var dayss = JSON.parse(days);

          dayss.forEach(day => {
            if(day == 'Monday'){
              $("#updateTask #monday").attr('checked', true);
            }
            else if( day == 'Tuesday')
            {
              $("#updateTask #tuesday").attr('checked', true);
            }
            else if( day == 'Wednesday')
            {
              $("#updateTask #wednesday").attr('checked', true);
            }
            else if( day == 'Thursday')
            {
              $("#updateTask #thursday").attr('checked', true);
            }
            else if( day == 'Friday')
            {
              $("#updateTask #friday").attr('checked', true);
            }
            else if( day == 'Saturday')
            {
              $("#updateTask #saturday").attr('checked', true);
            }
            else if( day == 'Sunday')
            {
              $("#updateTask #sunday").attr('checked', true);
            } 
          });
        }

        $("#updateTask #id").val(id);
        $("#updateTask #title").val(title);
        
        $("#updateTask").modal('show');
    }   

    function deleteTask(id)
    {
        $("#deleteTask #id").val(id);
        $("#deleteTask").modal('show');
    }   
</script>
@endsection