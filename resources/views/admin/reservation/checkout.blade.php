@extends('admin.layouts.app')
@section('title')
    <title>Zimmer Management</title>
@endsection
@section('extra_css')
<style>
    /* Customer name column - max width with text wrapping */
    #myTable tbody td:nth-child(3) {
        max-width: 250px;
        word-wrap: break-word;
        word-break: break-word;
        white-space: normal;
        line-height: 1.4;
    }
    
    /* Price plan selector - make it bigger */
    #myTable tbody td:nth-child(5) select {
        min-width: 120px;
        width: auto;
    }
    
    /* Ensure table cells don't overflow */
    #myTable td {
        vertical-align: middle;
        padding: 10px 8px;
    }
    
    /* Customer group header styling */
    .customer-group-header {
        background-color: #f8f9fa;
        font-weight: 600;
        border-top: 3px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
    }
    
    .customer-group-header td {
        padding: 16px 12px;
        vertical-align: middle;
    }
    
    .customer-group-header + tr {
        border-top: 1px solid #e9ecef;
    }
    
    .customer-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .customer-name {
        font-size: 1rem;
        margin-bottom: 4px;
    }
    
    .customer-balance {
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .customer-balance.positive {
        color: #198754;
    }
    
    .customer-balance.negative {
        color: #dc3545;
    }
    
    .wallet-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 4px 0;
    }
    
    .wallet-checkbox {
        margin-bottom: 4px;
    }
    
    .wallet-breakdown {
        font-size: 0.85rem;
        padding: 6px 8px;
        background-color: #e7f3ff;
        border-radius: 4px;
        margin-top: 4px;
        border-left: 3px solid #0d6efd;
    }
    
    .wallet-breakdown small {
        display: block;
        line-height: 1.5;
    }
    
    /* HelloCash section styling */
    .hellocash-section {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .hellocash-section .form-check-label {
        color: #6c757d;
    }
    
    .hellocash-section .form-check-input:checked + .form-check-label {
        color: #198754;
        font-weight: 500;
    }
    
    /* Add spacing between rows */
    #myTable tbody tr:not(.customer-group-header) {
        border-bottom: 1px solid #f0f0f0;
    }
    
    #myTable tbody tr:not(.customer-group-header):hover {
        background-color: #f8f9fa;
    }
    
    /* Improve input field spacing */
    #myTable tbody input[type="number"],
    #myTable tbody select {
        margin-bottom: 4px;
    }
    
    /* VAT info spacing */
    #myTable tbody small {
        display: block;
        margin-top: 6px;
        line-height: 1.4;
    }
    
    /* Table header spacing */
    #myTable thead th {
        padding: 12px 8px;
        font-weight: 600;
    }
    
    /* Add margin after customer group */
    .customer-group-header ~ tr[data-customer-id] {
        padding-left: 8px;
    }
    
    /* Summary section improvements */
    .checkoutTotal {
        padding-top: 8px;
    }
</style>
@endsection
@section('body')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
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
                                    @php 
                                        $total = 0;
                                        $vatPercentage = $vatPercentage ?? 20;
                                        $vatMode = config('app.vat_calculation_mode', 'exclusive');
                                        $rowIndex = 0;
                                    @endphp
                                    @if(isset($groupedReservations))
                                        @foreach($groupedReservations as $customerId => $group)
                                            @php
                                                $customer = $group['customer'];
                                                $customerBalance = $group['balance'];
                                                $customerReservations = $group['reservations'];
                                            @endphp
                                            
                                            {{-- Customer Group Header --}}
                                            <tr class="customer-group-header">
                                                <td colspan="3">
                                                    <div class="customer-info">
                                                        <div class="customer-name">
                                                            <strong>{{ $customer ? $customer->name . ' (ID: ' . $customer->id . ')' : 'Kein Kunde' }}</strong>
                                                        </div>
                                                        <div>
                                                            <span class="customer-balance {{ $customerBalance >= 0 ? 'positive' : 'negative' }}" id="customer_balance_{{ $customerId }}" data-original-balance="{{ $customerBalance }}">
                                                                Saldo: {{ $customerBalance >= 0 ? '+' : '' }}{{ number_format($customerBalance, 2, ',', '.') }}€
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td colspan="7">
                                                    <div class="d-flex justify-content-end align-items-start gap-4">
                                                        {{-- HelloCash / Registrierkasse Checkbox --}}
                                                        <div class="hellocash-section">
                                                            <div class="form-check mt-1">
                                                                <input class="form-check-input" type="checkbox" name="send_to_hellocash[{{ $customerId }}]" id="send_to_hellocash_{{ $customerId }}" value="1" onchange="handleHelloCashChange('{{ $customerId }}')">
                                                                <label class="form-check-label" for="send_to_hellocash_{{ $customerId }}">
                                                                    <i class="bx bx-receipt me-1"></i>Registrierkasse
                                                                </label>
                                                            </div>
                                                            <small id="hellocash_info_{{ $customerId }}" class="text-muted" style="display: none; font-size: 0.75rem;">
                                                                <i class="bx bx-info-circle"></i> Zahlungsart wird auf Bar fixiert
                                                            </small>
                                                        </div>
                                                        
                                                        {{-- Wallet Section --}}
                                                        <div class="wallet-section">
                                                            @if($customer && $customerBalance > 0)
                                                                <div class="form-check wallet-checkbox">
                                                                    <input class="form-check-input" type="checkbox" name="use_wallet[{{ $customerId }}]" id="use_wallet_{{ $customerId }}" value="1" onchange="handleWalletChange('{{ $customerId }}')">
                                                                    <label class="form-check-label" for="use_wallet_{{ $customerId }}">
                                                                        Guthaben verwenden (<span id="wallet_available_{{ $customerId }}">{{ number_format($customerBalance, 2, ',', '.') }}</span>€)
                                                                    </label>
                                                                </div>
                                                            @endif
                                                            <div id="wallet_breakdown_{{ $customerId }}" class="wallet-breakdown" style="display: none;">
                                                                <small>
                                                                    <strong>Guthaben:</strong> <span id="wallet_used_{{ $customerId }}">0.00</span>€<br>
                                                                    <strong>Bar:</strong> <span id="cash_payment_{{ $customerId }}">0.00</span>€
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            @foreach($customerReservations as $obj)
                                            @php
                                                // Calculate days based on configuration
                                                $checkinDate = \Carbon\Carbon::parse($obj->checkin_date)->startOfDay();
                                                $now = \Carbon\Carbon::now()->startOfDay();
                                                $daysDiff = $checkinDate->diffInDays($now);
                                                
                                                if ($daysDiff === 0) {
                                                    $days_between = 1;
                                                } else {
                                                    $calculationMode = config('app.days_calculation_mode', 'inclusive');
                                                    $days_between = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                                                } 

                                                // Determine current plan ID and price
                                                $current_plan_id = null;
                                                if(isset($obj->plan) && $obj->plan != null)
                                                {
                                                    if($days_between > 1)
                                                    {
                                                        $plan_title = $obj->plan->title;
                                                        $plan_price = $obj->plan->price;
                                                        $current_plan_id = $obj->plan->id;
                                                    }
                                                    else{
                                                        $plan_title = $obj->dog->day_plan_obj->title ?? '';
                                                        $plan_price = $obj->dog->day_plan_obj->price ?? 0;
                                                        $current_plan_id = $obj->dog->day_plan ?? null;
                                                    }
                                                }
                                                else{
                                                    $plan_title = $obj->dog->day_plan_obj->title ?? '';
                                                    $plan_price = $obj->dog->day_plan_obj->price ?? 0;
                                                    $current_plan_id = $obj->dog->day_plan ?? null;
                                                }
                                                
                                                // Calculate initial amounts based on VAT mode
                                                $basePrice = (double)$plan_price * (int)$days_between;
                                                
                                                if ($vatMode === 'inclusive') {
                                                    $initial_net = \App\Helpers\VATCalculator::getNetFromGross($basePrice, $vatPercentage);
                                                    $initial_vat = $basePrice - $initial_net;
                                                    $initial_gross = $basePrice;
                                                } else {
                                                    $initial_net = $basePrice;
                                                    $initial_vat = \App\Helpers\VATCalculator::calculateVATAmount($initial_net, $vatPercentage);
                                                    $initial_gross = $initial_net + $initial_vat;
                                                }
                                                
                                                $total = $total + $initial_gross;
                                            @endphp
                                            <tr data-customer-id="{{ $customerId }}">
                                                <td>{{$obj->dog->id}}</td>
                                                <td>{{$obj->dog->name}}</td>
                                                <td>{{$obj->dog->customer->name ?? 'N/A'}} ({{$obj->dog->customer->id ?? 'N/A'}})</td>
                                                <td>({{$days_between}} Tage)</td>
                                                <td>
                                                    <select class="form-control form-control-sm" id="plan_id{{$rowIndex}}" name="plan_id[]" onchange="updatePlanCost('{{$rowIndex}}')">
                                                        @foreach($plans as $plan)
                                                            <option value="{{$plan->id}}" 
                                                                data-price="{{$plan->price}}"
                                                                data-flat-rate="{{$plan->flat_rate}}"
                                                                {{ ($current_plan_id == $plan->id) ? 'selected' : '' }}>
                                                                {{$plan->title}}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="form-control payment-method-select" data-customer-id="{{ $customerId }}" id="payment_method_select_{{ $rowIndex }}" onchange="updatePaymentMethod('{{ $rowIndex }}')">
                                                        <option selected value="Bar">Bar</option>
                                                        <option value="Bank">Banküberweisung</option>
                                                    </select>
                                                    <input type="hidden" name="payment_method[]" id="payment_method_{{ $rowIndex }}" value="Bar">
                                                </td>
                                                <td>
                                                    <select required class="form-control" id="discount_select{{$rowIndex}}" onchange="updateDiscount('{{$rowIndex}}', '{{$customerId}}')">
                                                        <option selected value="0">0%</option>
                                                        <option value="10">10%</option>
                                                        <option value="15">15%</option>
                                                    </select>
                                                    <input type="hidden" name="discount[]" id="discount{{$rowIndex}}" value="0">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.10" class="form-control" id="special_cost{{$rowIndex}}" name="special_cost[]" value="0.00" oninput="recalculateRow('{{$rowIndex}}', '{{$customerId}}')">
                                                </td>
                                                <td>
                                                    <div style="margin-bottom: 4px;">
                                                        <input required type="number" step="0.10" class="form-control" id="invoice_amount{{$rowIndex}}" name="invoice_amount[]" value="{{ number_format($initial_gross, 2, '.', '')}}">
                                                    </div>
                                                    <small id="vat_info{{$rowIndex}}" style="font-size: 0.75rem; color:#155724; display: block; margin-top: 4px;">Netto: {{ number_format($initial_net, 2, ',', '.') }}€ + MwSt: {{ number_format($initial_vat, 2, ',', '.') }}€</small>
                                                </td>
                                                <td>
                                                    <input required type="number" step="0.01" class="form-control" id="received_amount{{$rowIndex}}" name="received_amount[]" value="{{ number_format($initial_gross, 2, '.', '')}}" oninput="changeTotal();">
                                                </td>
                                                <input type="hidden" class="form-control" name="res_id[]" value="{{$obj->id}}">
                                                <input type="hidden" class="form-control" name="base_cost[]" id="base_cost{{$rowIndex}}" value="{{ number_format($basePrice, 2, '.', '')}}">
                                                <input type="hidden" class="form-control" name="days[]" id="days{{$rowIndex}}" value="{{ (int)$days_between}}">
                                                <input type="hidden" class="form-control" name="plan_price{{$rowIndex}}" id="plan_price{{$rowIndex}}" value="{{$plan_price}}">
                                                <input type="hidden" class="form-control" id="net_amount{{$rowIndex}}" value="{{ number_format($initial_net, 2, '.', '')}}">
                                                <input type="hidden" class="form-control" id="vat_amount_row{{$rowIndex}}" value="{{ number_format($initial_vat, 2, '.', '')}}">
                                                <input type="hidden" class="form-control customer-row" data-customer-id="{{ $customerId }}" value="{{$rowIndex}}">
                                            </tr>
                                            @php $rowIndex++; @endphp
                                            @endforeach
                                        @endforeach
                                    @else
                                        {{-- Fallback for old structure --}}
                                        @foreach($reservations ?? [] as $obj)
                                            {{-- Keep old structure as fallback --}}
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <hr style="margin: 24px 0;">
                        <div class="row  mb-4">
                            <div class="col-12">
                                <table class="table table-sm table-bordered" style="background-color: #f8f9fa; width: 100%;">
                                    <colgroup>
                                        <col style="width: 100px;">
                                        <col style="width: 130px;">
                                        <col style="width: 100px;">
                                        <col style="width: 100px;">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="padding: 10px;">Netto</th>
                                            <th class="text-center" style="padding: 10px;">MwSt (<span id="vat_percentage_display">{{$vatPercentage}}</span>%)</th>
                                            <th class="text-center" style="padding: 10px;">Brutto</th>
                                            <th class="text-center" style="padding: 10px;">Gesamt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-center" style="padding: 12px;"><span id="vat_net_total" class="fw-bold">0.00€</span></td>
                                            <td class="text-center" style="padding: 12px;"><span id="vat_total" class="fw-bold">0.00€</span></td>
                                            <td class="text-center" style="padding: 12px;"><span id="vat_gross_total" class="fw-bold text-primary">0.00€</span></td>
                                            <td class="text-center" style="padding: 12px;"><span id="totalAmount" class="fw-bold" style="color: #155724; font-size: 1.05rem;">{{number_format($total, 2, ',', '.')}}€</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" id="checkoutSubmitBtn" class="btn btn-primary px-4 py-2">
                                <span id="submitBtnText" aria-live="polite" aria-atomic="true">Aktualisieren</span>
                                <span id="submitBtnLoader" class="spinner-border spinner-border-sm" style="display: none;" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('extra_js')

<script>
    // Invoices are displayed at the top of the page for user to open manually
    // No auto-opening to avoid popup blocker issues
    @if(Session::has('bulk_checkout_invoices'))
        $(document).ready(function() {
            var invoices = @json(Session::get('bulk_checkout_invoices'));
        });
    @endif
</script>

<script>
    // Store VAT settings from backend
    var vatPercentage = {{ $vatPercentage ?? 20 }};
    var vatMode = '{{ config("app.vat_calculation_mode", "exclusive") }}';
    
    // Store customer balances
    var customerBalances = {};
    @if(isset($groupedReservations))
        @foreach($groupedReservations as $customerId => $group)
            customerBalances[{{ $customerId }}] = {{ $group['balance'] }};
        @endforeach
    @endif
    
    // Track which amounts have been manually edited
    var manuallyEditedReceived = {};
    var manuallyEditedInvoice = {};
    
    function getNetFromGross(grossAmount) {
        var net = grossAmount / (1 + (vatPercentage / 100));
        return parseFloat(net.toFixed(2));
    }
    
    function calculateVATFromNet(netAmount) {
        return parseFloat((netAmount * (vatPercentage / 100)).toFixed(2));
    }
    
    function updateDiscount(index, customerId) {
        var discountSelect = $("#discount_select"+index);
        var discountValue = parseInt(discountSelect.val()) || 0;
        // Update the hidden field that gets submitted
        $("#discount"+index).val(discountValue);
        recalculateRow(index, customerId);
    }
    
    function updatePlanCost(index)
    {
        var planSelect = $("#plan_id"+index);
        var selectedOption = planSelect.find('option:selected');
        var planPrice = parseFloat(selectedOption.data('price')) || 0;
        var isFlatRate = parseInt(selectedOption.data('flat-rate')) === 1;
        var days = parseInt($("#days"+index).val()) || 1;
        var customerId = $("tr:has(#plan_id"+index+")").data('customer-id');
        
        // For flat rate plans, don't multiply by days
        var newBaseCost = isFlatRate ? planPrice : (planPrice * days);
        
        $("#base_cost"+index).val(newBaseCost.toFixed(2));
        $("#plan_price"+index).val(planPrice.toFixed(2));
        
        // Hide/disable discount section for flat rate plans
        var discountSelect = $("#discount_select"+index);
        if (isFlatRate) {
            discountSelect.val('0').prop('disabled', true).css('opacity', '0.6');
            // Force hidden field to 0 for flat rate
            $("#discount"+index).val('0');
        } else {
            discountSelect.prop('disabled', false).css('opacity', '1');
        }
        
        recalculateRow(index, customerId);
    }

    function recalculateRow(index, customerId)
    {
        var baseCost = parseFloat($("#base_cost"+index).val()) || 0;
        var specialCost = parseFloat($("#special_cost"+index).val()) || 0;
        var discount = parseInt($("#discount"+index).val()) || 0;
        
        var netTotal;
        if (vatMode === 'inclusive') {
            var baseNet = getNetFromGross(baseCost);
            var specialNet = getNetFromGross(specialCost);
            netTotal = baseNet + specialNet;
        } else {
            netTotal = baseCost + specialCost;
        }
        
        if(discount > 0) {
            netTotal = netTotal * (1 - (discount / 100));
        }
        netTotal = parseFloat(netTotal.toFixed(2));
        
        var vatAmount = calculateVATFromNet(netTotal);
        var grossTotal = netTotal + vatAmount;
        grossTotal = parseFloat(grossTotal.toFixed(2));

        if (manuallyEditedInvoice[index]) {
            grossTotal = parseFloat($("#invoice_amount"+index).val()) || 0;
            netTotal = getNetFromGross(grossTotal);
            vatAmount = parseFloat((grossTotal - netTotal).toFixed(2));
        }
        
        $("#net_amount"+index).val(netTotal.toFixed(2));
        $("#vat_amount_row"+index).val(vatAmount.toFixed(2));
        if (!manuallyEditedInvoice[index]) {
            $("#invoice_amount"+index).val(grossTotal.toFixed(2));
        }
        $("#vat_info"+index).html("Netto: " + netTotal.toFixed(2) + "€ + MwSt: " + vatAmount.toFixed(2) + "€").css("color", "#155724");
        
        // Update received amount based on wallet usage
        updateReceivedAmountForRow(index, customerId, grossTotal);
        
        updateVATTotals();
        changeTotal();
        updateCustomerTotals(customerId);
    }
    
    function updateReceivedAmountForRow(index, customerId, grossTotal) {
        // Skip if user has manually edited this field
        if (manuallyEditedReceived[index]) {
            return;
        }
        
        var useWallet = $("#use_wallet_" + customerId).is(':checked');
        var customerBalance = customerBalances[customerId] || 0;
        
        if (useWallet && customerBalance > 0) {
            // Calculate wallet usage for this customer's total
            var customerTotal = getCustomerTotal(customerId);
            var walletUsed = Math.min(customerBalance, customerTotal);
            var cashNeeded = Math.max(0, customerTotal - walletUsed);
            
            // Distribute cash needed proportionally
            var cashPerRow = customerTotal > 0 ? (cashNeeded / customerTotal) : 0;
            var rowCash = grossTotal * cashPerRow;
            $("#received_amount"+index).val(rowCash.toFixed(2));
        } else {
            $("#received_amount"+index).val(grossTotal.toFixed(2));
        }
    }
    
    function getCustomerTotal(customerId) {
        var total = 0;
        $("tr[data-customer-id='" + customerId + "']").each(function() {
            var index = $(this).find('.customer-row').val();
            if (index !== undefined) {
                total += parseFloat($("#invoice_amount" + index).val()) || 0;
            }
        });
        return total;
    }
    
    function handleWalletChange(customerId) {
        var useWallet = $("#use_wallet_" + customerId).is(':checked');
        var customerBalance = customerBalances[customerId] || 0;
        var customerTotal = getCustomerTotal(customerId);
        
        // Clear manual edit flags for this customer's rows when wallet is toggled
        $("tr[data-customer-id='" + customerId + "']").each(function() {
            var index = $(this).find('.customer-row').val();
            if (index !== undefined) {
                delete manuallyEditedReceived[index];
            }
        });
        
        if (useWallet && customerBalance > 0) {
            $("#wallet_breakdown_" + customerId).show();
            var walletUsed = Math.min(customerBalance, customerTotal);
            var newBalance = customerBalance - walletUsed;
            updateCustomerSaldoDisplay(customerId, newBalance);
        } else {
            $("#wallet_breakdown_" + customerId).hide();
            // Restore original balance when wallet is unchecked
            updateCustomerSaldoDisplay(customerId, customerBalance);
            // Reset received amounts to invoice amounts
            $("tr[data-customer-id='" + customerId + "']").each(function() {
                var index = $(this).find('.customer-row').val();
                if (index !== undefined) {
                    var invoiceAmount = parseFloat($("#invoice_amount" + index).val()) || 0;
                    $("#received_amount" + index).val(invoiceAmount.toFixed(2));
                }
            });
        }
        
        updateCustomerTotals(customerId);
    }
    
    function updateCustomerTotals(customerId) {
        var customerTotal = getCustomerTotal(customerId);
        var useWallet = $("#use_wallet_" + customerId).is(':checked');
        var customerBalance = customerBalances[customerId] || 0;
        
        if (useWallet && customerBalance > 0) {
            var walletUsed = Math.min(customerBalance, customerTotal);
            var cashNeeded = Math.max(0, customerTotal - walletUsed);
            var newBalance = customerBalance - walletUsed;
            
            $("#wallet_used_" + customerId).text(walletUsed.toFixed(2));
            $("#cash_payment_" + customerId).text(cashNeeded.toFixed(2));
            
            // Update saldo display
            updateCustomerSaldoDisplay(customerId, newBalance);
            
            // Update received amounts for this customer's rows proportionally (only if not manually edited)
            var cashPerRow = customerTotal > 0 ? (cashNeeded / customerTotal) : 0;
            $("tr[data-customer-id='" + customerId + "']").each(function() {
                var index = $(this).find('.customer-row').val();
                if (index !== undefined && !manuallyEditedReceived[index]) {
                    var rowTotal = parseFloat($("#invoice_amount" + index).val()) || 0;
                    var rowCash = rowTotal * cashPerRow;
                    $("#received_amount" + index).val(rowCash.toFixed(2));
                }
            });
        }
        // When wallet is NOT used, DO NOT reset received amounts - let user edit freely
        
        changeTotal();
    }
    
    function updateCustomerSaldoDisplay(customerId, newBalance) {
        var balanceElement = $("#customer_balance_" + customerId);
        
        if (balanceElement.length) {
            var balanceText = (newBalance >= 0 ? '+' : '') + newBalance.toFixed(2).replace('.', ',') + '€';
            var balanceClass = newBalance >= 0 ? 'positive' : 'negative';
            
            balanceElement
                .text('Saldo: ' + balanceText)
                .removeClass('positive negative')
                .addClass(balanceClass);
        }
    }
    
    function updateVATTotals()
    {
        var totalNet = 0;
        var totalVat = 0;
        var totalGross = 0;
        
        $("input[id^='net_amount']").each(function() {
            totalNet += parseFloat($(this).val()) || 0;
        });
        
        $("input[id^='vat_amount_row']").each(function() {
            totalVat += parseFloat($(this).val()) || 0;
        });
        
        $("input[id^='invoice_amount']").each(function() {
            totalGross += parseFloat($(this).val()) || 0;
        });
        
        $("#vat_net_total").text(totalNet.toFixed(2) + "€");
        $("#vat_total").text(totalVat.toFixed(2) + "€");
        $("#vat_gross_total").text(totalGross.toFixed(2) + "€");
    }

    function changeTotal()
    {
        var total = 0;
        var inputs = $("input[name='received_amount[]']");
        inputs.map((i, item) => {
            var val = (item.value == '' || item.value == undefined) ? 0 : item.value;
            total += parseFloat(val);
        });
        $("#totalAmount").text(total.toFixed(2).replace('.', ',') + "€");
        
        // Update projected balance for each customer
        updateAllCustomerBalances();
    }
    
    function updateAllCustomerBalances() {
        // Get all unique customer IDs
        var customerIds = [];
        $(".customer-group-header").each(function() {
            var row = $(this).next("tr");
            var customerId = row.data('customer-id');
            if (customerId && customerIds.indexOf(customerId) === -1) {
                customerIds.push(customerId);
            }
        });
        
        // Also get from data attributes directly
        $("tr[data-customer-id]").each(function() {
            var customerId = $(this).data('customer-id');
            if (customerId && customerIds.indexOf(customerId) === -1) {
                customerIds.push(customerId);
            }
        });
        
        // Update balance for each customer
        customerIds.forEach(function(customerId) {
            updateCustomerBalanceDisplay(customerId);
        });
    }
    
    function updateCustomerBalanceDisplay(customerId) {
        var existingBalance = customerBalances[customerId] || 0;
        var useWallet = $("#use_wallet_" + customerId).is(':checked');
        
        // Calculate totals for this customer
        var customerInvoiceTotal = 0;
        var customerReceivedTotal = 0;
        
        $("tr[data-customer-id='" + customerId + "']").each(function() {
            var index = $(this).find('.customer-row').val();
            if (index !== undefined) {
                customerInvoiceTotal += parseFloat($("#invoice_amount" + index).val()) || 0;
                customerReceivedTotal += parseFloat($("#received_amount" + index).val()) || 0;
            }
        });
        
        // Calculate wallet usage
        var walletUsed = 0;
        if (useWallet && existingBalance > 0) {
            walletUsed = Math.min(existingBalance, customerInvoiceTotal);
        }
        
        // Calculate effective received (wallet + cash)
        var effectiveReceived = walletUsed + customerReceivedTotal;
        
        // Calculate advance payment or remaining
        var advancePayment = 0;
        var currentRemaining = 0;
        
        if (effectiveReceived > customerInvoiceTotal) {
            // Customer paid more (advance payment)
            advancePayment = effectiveReceived - customerInvoiceTotal;
        } else if (effectiveReceived < customerInvoiceTotal) {
            // Customer paid less (still owes)
            currentRemaining = customerInvoiceTotal - effectiveReceived;
        }
        
        // Calculate projected balance: existing + advance - remaining - walletUsed
        // Positive = customer credit (GREEN), Negative = customer debt (RED)
        var currentNetBalance = advancePayment - currentRemaining - walletUsed;
        var projectedBalance = existingBalance + currentNetBalance;
        
        // Update display
        var balanceElement = $("#customer_balance_" + customerId);
        var sign = projectedBalance >= 0 ? '+' : '';
        var formattedBalance = sign + projectedBalance.toFixed(2).replace('.', ',') + '€';
        balanceElement.text('Saldo: ' + formattedBalance);
        
        // Update color
        balanceElement.removeClass('positive negative');
        if (projectedBalance > 0) {
            balanceElement.addClass('positive');
        } else if (projectedBalance < 0) {
            balanceElement.addClass('negative');
        }
    }

    $(document).ready(function(){
        // Initialize VAT totals on page load for all rows
        var maxIndex = 0;
        $("input[id^='base_cost']").each(function() {
            var id = $(this).attr('id');
            var index = id.replace('base_cost', '');
            maxIndex = Math.max(maxIndex, parseInt(index) || 0);
        });
        
        // Recalculate all rows to initialize VAT and flat rate states
        for(var i = 0; i <= maxIndex; i++) {
            if($("#base_cost"+i).length) {
                // Initialize flat rate discount state
                var planSelect = $("#plan_id"+i);
                var selectedOption = planSelect.find('option:selected');
                var isFlatRate = parseInt(selectedOption.data('flat-rate')) === 1;
                var discountSelect = $("#discount_select"+i);
                if (isFlatRate) {
                    discountSelect.val('0').prop('disabled', true).css('opacity', '0.6');
                    // Force hidden field to 0 for flat rate
                    $("#discount"+i).val('0');
                } else {
                    discountSelect.prop('disabled', false).css('opacity', '1');
                }
                
                var customerId = $("tr:has(#base_cost"+i+")").data('customer-id');
                if (customerId) {
                    recalculateRow(i, customerId);
                } else {
                    recalculateRow(i, null);
                }
            }
        }
        
        
        // Update grand total when received amounts change (allow manual edits)
        $("input[name='received_amount[]']").on('input', function() {
            // Extract the row index from the id (e.g., "received_amount0" -> "0")
            var id = $(this).attr('id');
            var index = id.replace('received_amount', '');
            
            // Mark this field as manually edited
            manuallyEditedReceived[index] = true;
            
            changeTotal();
        });

        // Allow manual invoice amount override per row.
        $("input[name='invoice_amount[]']").on('input', function() {
            var id = $(this).attr('id');
            var index = id.replace('invoice_amount', '');
            manuallyEditedInvoice[index] = true;

            var customerId = $("tr:has(#invoice_amount"+index+")").data('customer-id');
            var gross = parseFloat($(this).val()) || 0;
            var net = getNetFromGross(gross);
            var vat = parseFloat((gross - net).toFixed(2));

            $("#net_amount"+index).val(net.toFixed(2));
            $("#vat_amount_row"+index).val(vat.toFixed(2));
            $("#vat_info"+index).html("Netto: " + net.toFixed(2) + "€ + MwSt: " + vat.toFixed(2) + "€").css("color", "#155724");

            updateVATTotals();
            changeTotal();
            if (customerId) {
                updateCustomerTotals(customerId);
            }
        });

        // Prevent double submission (only on the checkout form)
        var checkoutForm = $('#checkoutSubmitBtn').closest('form');
        if (checkoutForm.length) {
            checkoutForm.on('submit', function(e) {
                var submitBtn = $('#checkoutSubmitBtn');
                if (submitBtn.prop('disabled')) {
                    e.preventDefault();
                    return false;
                }
                
                // Disable button and show loader
                submitBtn.prop('disabled', true);
                $('#submitBtnText').text('Wird verarbeitet...');
                $('#submitBtnLoader').show();
            });
        }
    });
    
    // Update hidden payment method field when select changes
    function updatePaymentMethod(rowIndex) {
        var selectValue = $('#payment_method_select_' + rowIndex).val();
        $('#payment_method_' + rowIndex).val(selectValue);
    }
    
    // Handle HelloCash checkbox change for a specific customer
    function handleHelloCashChange(customerId) {
        var isChecked = $('#send_to_hellocash_' + customerId).is(':checked');
        var infoElement = $('#hellocash_info_' + customerId);
        
        // Find all payment method selects for this customer's dogs
        var paymentSelects = $('select.payment-method-select[data-customer-id="' + customerId + '"]');
        
        if (isChecked) {
            // Show info message
            infoElement.show();
            
            // Force all payment methods to Bar and disable the select
            paymentSelects.each(function() {
                var selectId = $(this).attr('id');
                var rowIndex = selectId.replace('payment_method_select_', '');
                
                $(this).val('Bar');
                $(this).prop('disabled', true);
                $(this).css('opacity', '0.6');
                $(this).css('background-color', '#e9ecef');
                
                // Update the hidden field too
                $('#payment_method_' + rowIndex).val('Bar');
            });
        } else {
            // Hide info message
            infoElement.hide();
            
            // Re-enable the payment method selects
            paymentSelects.each(function() {
                $(this).prop('disabled', false);
                $(this).css('opacity', '1');
                $(this).css('background-color', '');
            });
        }
    }

</script>
@endsection
