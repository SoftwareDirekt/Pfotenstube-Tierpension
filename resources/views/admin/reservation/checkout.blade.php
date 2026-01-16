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
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-header">Hunde in den Zimmern</h5>
                    </div>
                </div>
                <form action="{{route('admin.dogs.rooms.checkout-update')}}" method="POST">
                    @csrf
                    <div class="table-responsive text-nowrap mb-2">
                        <table class="table" id="myTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Hund ID</th>
                                    <th>Hund Name</th>
                                    <th>Kunde</th>
                                    <th>Termine</th>
                                    <th>Preispläne</th>
                                    <th>Zahlungsart</th>
                                    <th>Rabatt</th>
                                    <th>Zusätzliche Kosten</th>
                                    <th>Rechnungsbetrag</th>
                                    <th>Betrag Erhalten</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @php $total = 0; @endphp
                                @foreach($reservations as $obj)
                                @php
                                    // Calculate days based on configuration (inclusive or exclusive)
                                    // Same-day (29-29) always counts as 1 day regardless of mode
                                    // Inclusive: both checkin date and today count (29-30 = 2 days, 29-31 = 3 days)
                                    // Exclusive: days between count, same-day is 1 (29-30 = 1 day, 29-31 = 2 days)
                                    // Normalize dates to start of day for consistent calculation
                                    $checkinDate = \Carbon\Carbon::parse($obj->checkin_date)->startOfDay();
                                    $now = \Carbon\Carbon::now()->startOfDay();
                                    $daysDiff = $checkinDate->diffInDays($now);
                                    
                                    // Same-day checkin/checkout always counts as 1 day
                                    if ($daysDiff === 0) {
                                        $days_between = 1;
                                    } else {
                                        $calculationMode = config('app.days_calculation_mode', 'inclusive');
                                        $days_between = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                                    }

                                    if(isset($obj->plan) && $obj->plan != null)
                                    {
                                        if($days_between > 1)
                                        {
                                            $plan_title = $obj->plan->title;
                                            $plan_price = $obj->plan->price;
                                            $total = $total + ((double)$obj->plan->price * $days_between);

                                        }
                                        else{
                                            $plan_title = $obj->dog->day_plan_obj->title;
                                            $plan_price = $obj->dog->day_plan_obj->price;
                                            $total = $total + ((double)$obj->dog->day_plan_obj->price * $days_between);
                                        }
                                    }
                                    else{
                                        $plan_title = '';
                                        $plan_price = 0;
                                    }

                                @endphp
                                <tr>
                                    <td>{{$obj->dog->id}}</td>
                                    <td>{{$obj->dog->name}}</td>
                                    <td>{{$obj->dog->customer->name}} ({{$obj->dog->customer->id}})</td>
                                    <td>({{$days_between}} Tage)</td>
                                    <td>
                                        <?php
                                            $plan_title;
                                        ?>
                                    </td>
                                    <td>
                                        <select required class="form-control" name="payment_method[]">
                                            <option selected value="Bar">Bar</option>
                                            <option value="Bank">Bankuberweisung</option>
                                            <option value="Nicht bezahlt">Nicht bezahlt</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select required class="form-control" id="discount{{$loop->index}}" name="discount[]" onchange="recalculateRow('{{$loop->index}}')">
                                            <option selected value="0">0%</option>
                                            <option value="10">10%</option>
                                            <option value="15">15%</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.10" class="form-control" id="special_cost{{$loop->index}}" name="special_cost[]" value="0.00" oninput="recalculateRow('{{$loop->index}}')">
                                    </td>
                                    <td>
                                        <input required type="number" step="0.10" class="form-control" id="invoice_amount{{$loop->index}}" name="invoice_amount[]" value="{{ number_format((double)$plan_price * (int)$days_between, 2, '.', '')}}" oninput="changeTotal()">
                                    </td>
                                    <td>
                                        <input required type="number" step="0.10" class="form-control" id="received_amount{{$loop->index}}" name="received_amount[]" value="{{ number_format((double)$plan_price * (int)$days_between, 2, '.', '')}}" oninput="changeTotal()">
                                    </td>
                                    <input type="hidden" class="form-control" name="res_id[]" value="{{$obj->id}}">
                                    <input type="hidden" class="form-control" name="base_cost[]" id="base_cost{{$loop->index}}" value="{{ (double)$plan_price * (int)$days_between}}">
                                    <input type="hidden" class="form-control" name="days[]" id="days{{$loop->index}}" value="{{ (int)$days_between}}">
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <div class="row d-flex justify-content-end px-1 mb-3">
                        <div class="col-md-12 text-end pe-5 checkoutTotal">
                            <p>Gesamt: <span id="totalAmount">{{$total}}</span><span>&euro;</span></p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <div class="">
                            <button class="btn btn-secondary p-1">Aktualisieren</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('extra_js')

<script>
    function recalculateRow(index)
    {
        var baseCost = parseFloat($("#base_cost"+index).val()) || 0;
        var specialCost = parseFloat($("#special_cost"+index).val()) || 0;
        var discount = parseInt($("#discount"+index).val()) || 0;
        
        // Calculate total before discount
        var totalBeforeDiscount = baseCost + specialCost;
        
        // Apply discount
        var totalAfterDiscount = totalBeforeDiscount;
        if(discount > 0) {
            totalAfterDiscount = totalBeforeDiscount * (1 - (discount / 100));
        }
        
        // Update invoice and received amount
        $("#invoice_amount"+index).val(totalAfterDiscount.toFixed(2));
        $("#received_amount"+index).val(totalAfterDiscount.toFixed(2));
        
        // Update grand total
        changeTotal();
    }
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

    $("#selectAll").on('click', function(){
        if($("#selectAll").is(':checked'))
        {
            $(".mycheck").prop('checked', true);
        }else{
            $(".mycheck").prop('checked', false);
        }
    })

    function changeTotal()
    {
        var total = 0;
        var inputs = $("input[name='received_amount[]']");
        inputs.map((i, item) => {
            var val = (item.value == '' || item.value == undefined) ? 0 : item.value;
            total += parseFloat(val);
        });
        $("#totalAmount").text(total.toFixed(2));
    }

    $(document).ready(function(){

        // $("input[name='received_amount']").on('change', function(){
        //     console.log(this.value)
        // })
    });

</script>
@endsection
