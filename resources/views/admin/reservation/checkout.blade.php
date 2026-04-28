@extends('admin.layouts.app')
@section('title')
    <title>Checkout</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
<style>
    #myTable td, #myTable th { vertical-align: middle; }
    .customer-group-header { background-color: #eef2f7; font-weight: 600; border-top: 2px solid #d0d8e4; }
    .additional-cost-input .input-group-text { min-width: 140px; }

    .plan-tag {
        display: inline-block;
        background: #e8f4fd;
        border: 1px solid #bee3f8;
        border-radius: 6px;
        padding: 3px 9px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1a4a72;
        white-space: nowrap;
    }
    .plan-price-label {
        font-size: 0.72rem;
        color: #718096;
        margin-top: 3px;
    }
    .readonly-amount {
        background-color: #f1f5f9 !important;
        color: #374151 !important;
        border-color: #d1d5db !important;
        cursor: default;
        font-weight: 600;
    }
    .amount-received-display {
        background: #f0fdf4;
        border: 1px solid #86efac;
        border-radius: 6px;
        padding: 5px 10px;
        font-weight: 700;
        color: #15803d;
        font-size: 0.9rem;
        text-align: center;
        min-width: 90px;
        display: block;
    }
    #myTable thead th { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
    #myTable tbody tr:not(.customer-group-header):hover { background-color: #fafbfc; }
    .vat-info-text { font-size: 0.72rem; color: #155724; display: block; margin-top: 4px; }
    .checkout-bulk-only .bulk-customer-card { border-radius: 12px; border: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #fff 100%); }
    .checkout-bulk-only .bulk-summary-bar { background: #f1f5f9; border-radius: 0 0 12px 12px; }
    .checkout-bulk-only.checkout-bulk-full {
        width: 100%;
        max-width: 100%;
        min-height: calc(100vh - 5.5rem);
        margin-left: 0;
        margin-right: 0;
        box-sizing: border-box;
    }
    .checkout-bulk-only .bulk-customer-meta { line-height: 1.55; }
    .checkout-bulk-only .bulk-customer-meta .bulk-customer-meta-line { display: block; }
</style>
@endsection
@section('body')
@if(!empty($bulkCheckoutOnly))
@php
    $c = $checkoutCustomer ?? null;
    $vatPct = $vatPercentage ?? 20;
    $vatModeBulk = config('app.vat_calculation_mode', 'exclusive');
    $sumDue = 0;
    $sumPaid = 0;
    $sumRem = 0;
    $sumDogLine = 0;
    foreach ($reservationGroups ?? [] as $gData) {
        $g = $gData['group'];
        $sumDue += (float) $g->total_due;
        $paid = (float) $g->activeEntries()->sum('amount');
        $sumPaid += $paid;
        $sumRem += max(0, round((float) $g->total_due - $paid, 2));
        foreach ($gData['reservations'] as $obj) {
            $checkinDate = \Carbon\Carbon::parse($obj->checkin_date)->startOfDay();
            $now = \Carbon\Carbon::now()->startOfDay();
            $daysDiff = $checkinDate->diffInDays($now);
            $days_between = ($daysDiff === 0) ? 1 : ((config('app.days_calculation_mode', 'inclusive') === 'inclusive') ? $daysDiff + 1 : $daysDiff);
            $plan_price = $obj->plan?->price ?? 0;
            $basePrice = (double) $plan_price * (int) $days_between;
            if ($vatModeBulk === 'inclusive') {
                $sumDogLine += $basePrice;
            } else {
                $sumDogLine += $basePrice * (1 + ($vatPct / 100));
            }
        }
    }
@endphp
<div class="container-fluid flex-grow-1 py-3 checkout-bulk-only checkout-bulk-full">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 mt-3">
        <div>
            <h4 class="mb-0">Mehrfachkasse</h4>
            <p class="text-muted small mb-0">Gruppen-Checkout für einen Kunden</p>
        </div>
        <a href="{{ route('admin.dogs.in.rooms') }}" class="btn btn-outline-secondary btn-sm">Zurück zur Auswahl</a>
    </div>

    <div class="card bulk-customer-card shadow-sm mb-4">
        <div class="card-body py-4 px-4">
            <div class="row align-items-start g-4">
                <div class="col-12 col-lg-7">
                    <h4 class="mb-3">{{ $c->name ?? 'Kunde' }}</h4>
                    @if($c)
                        <div class="bulk-customer-meta text-body-secondary d-flex flex-column gap-2">
                            <span class="bulk-customer-meta-line">Kunden-Nr. {{ $c->id_number ?? '—' }}</span>
                            <span class="bulk-customer-meta-line">Telefonnummer {{ $c->phone ? $c->phone : '—' }}</span>
                        </div>
                    @endif
                </div>
                <div class="col-12 col-lg-5 text-lg-end">
                    <div class="small text-muted">Summe Gruppe(n)</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($sumDue, 2, ',', '.') }}€</div>
                    <div class="small">Bezahlt: <span class="text-success">{{ number_format($sumPaid, 2, ',', '.') }}€</span></div>
                    <div class="small">Rest: <span class="{{ $sumRem > 0.01 ? 'text-danger' : 'text-success' }} fw-semibold">{{ number_format($sumRem, 2, ',', '.') }}€</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 flex-grow-1 d-flex flex-column">
                <div class="card-header bg-white border-bottom py-3">
                    <strong>Gruppen & Hunde</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table mb-0" id="myTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Hund ID</th>
                                    <th>Hund Name</th>
                                    <th>Termine</th>
                                    <th>Preisplan</th>
                                    <th>Positionsbetrag</th>
                                    <th class="text-end" style="min-width: 280px;">Gruppe / Zahlung</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @foreach($reservationGroups as $gid => $gData)
                                    @php
                                        $grp = $gData['group'];
                                        $grpCustomer = $gData['customer'];
                                        $grpReservations = $gData['reservations'];
                                        $grpTotalDue = (float) $grp->total_due;
                                        $grpPaid = (float) $grp->activeEntries()->sum('amount');
                                        $grpRemaining = max(0, round($grpTotalDue - $grpPaid, 2));
                                        $grpDogNames = collect($grpReservations)->map(fn ($r) => $r->dog?->name ?? '?')->implode(', ');
                                    @endphp
                                    <tr class="customer-group-header" style="background-color: #dbeafe;">
                                        <td colspan="4">
                                            <i class="mdi mdi-account-group me-1"></i>
                                            <strong>Gruppe G-{{ $grp->id }}</strong>
                                            <span class="text-muted ms-2">({{ count($grpReservations) }} Hunde: {{ $grpDogNames }})</span>
                                        </td>
                                        <td class="fw-bold align-middle">
                                            {{ number_format($grpTotalDue, 2, ',', '.') }}€
                                            <div class="small fw-normal text-muted">Bezahlt {{ number_format($grpPaid, 2, ',', '.') }}€ · Rest {{ number_format($grpRemaining, 2, ',', '.') }}€</div>
                                        </td>
                                        <td class="text-end align-middle">
                                            @if($grpRemaining > 0.01)
                                                <form action="{{ route('admin.dogs.rooms.group-checkout') }}" method="POST" class="d-inline-flex flex-wrap align-items-center justify-content-end gap-2" onsubmit="this.querySelector('button').disabled=true;">
                                                    @csrf
                                                    <input type="hidden" name="group_id" value="{{ $grp->id }}">
                                                    <select name="gateway" class="form-control form-control-sm" style="width:auto;">
                                                        <option value="Bar">Bar</option>
                                                        <option value="Bank">Bank</option>
                                                    </select>
                                                    <input type="number" name="received_amount" step="0.01" value="{{ number_format($grpRemaining, 2, '.', '') }}" class="form-control form-control-sm" style="width:110px;">
                                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                                                        <i class="mdi mdi-check-all me-1"></i>Gruppen-Checkout
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.dogs.rooms.group-checkout') }}" method="POST" class="d-inline" onsubmit="this.querySelector('button').disabled=true;">
                                                    @csrf
                                                    <input type="hidden" name="group_id" value="{{ $grp->id }}">
                                                    <input type="hidden" name="received_amount" value="0">
                                                    <input type="hidden" name="gateway" value="Bar">
                                                    <button type="submit" class="btn btn-sm btn-success text-nowrap">
                                                        <i class="mdi mdi-check-all me-1"></i>Gruppen-Checkout (bezahlt)
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @foreach($grpReservations as $obj)
                                        @php
                                            $checkinDate = \Carbon\Carbon::parse($obj->checkin_date)->startOfDay();
                                            $now = \Carbon\Carbon::now()->startOfDay();
                                            $daysDiff = $checkinDate->diffInDays($now);
                                            $days_between = ($daysDiff === 0) ? 1 : ((config('app.days_calculation_mode', 'inclusive') === 'inclusive') ? $daysDiff + 1 : $daysDiff);
                                            $plan_title = $obj->plan?->title ?? '';
                                            $plan_price = $obj->plan?->price ?? 0;
                                            $basePrice = (double) $plan_price * (int) $days_between;
                                            if ($vatModeBulk === 'inclusive') {
                                                $g = $basePrice;
                                            } else {
                                                $g = $basePrice * (1 + ($vatPct / 100));
                                            }
                                        @endphp
                                        <tr style="background-color:#f0f7ff;">
                                            <td>{{ $obj->dog->id }}</td>
                                            <td>{{ $obj->dog->name }}</td>
                                            <td>({{ $days_between }} Tage)</td>
                                            <td>
                                                <div class="plan-tag">{{ $plan_title ?: 'Kein Plan' }}</div>
                                                <div class="plan-price-label">€{{ number_format($plan_price, 2, ',', '.') }} / Tag</div>
                                            </td>
                                            <td class="fw-bold">{{ number_format($g, 2, ',', '.') }}€</td>
                                            <td class="text-muted small text-end">via Gruppe G-{{ $grp->id }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bulk-summary-bar border-0 py-3 px-4">
                    <div class="row text-center text-md-start g-2">
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Hunde-Zeilen (Brutto-Ansicht)</div>
                            <div class="fw-bold">{{ number_format($sumDogLine, 2, ',', '.') }}€</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Gruppe gesamt</div>
                            <div class="fw-bold">{{ number_format($sumDue, 2, ',', '.') }}€</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Anzahlungen</div>
                            <div class="fw-bold text-success">{{ number_format($sumPaid, 2, ',', '.') }}€</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Offen</div>
                            <div class="fw-bold {{ $sumRem > 0.01 ? 'text-danger' : 'text-success' }}">{{ number_format($sumRem, 2, ',', '.') }}€</div>
                        </div>
                    </div>
                </div>
            </div>
</div>
@else
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
                    <form action="{{route('admin.dogs.rooms.checkout-update')}}" method="POST" autocomplete="off">
                        @csrf
                        <div class="table-responsive text-nowrap mb-2">
                            <table class="table" id="myTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hund ID</th>
                                        <th>Hund Name</th>
                                        <th>Kunde</th>
                                        <th>Termine</th>
                                        <th>Preisplaene</th>
                                        <th>Rabatt</th>
                                        <th>Zusatzkosten</th>
                                        <th>Rechnungsbetrag</th>
                                        <th>Anzahlung</th>
                                        <th>Restbetrag</th>
                                        <th>Zahlungsart</th>
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

                                    {{-- ═══ Grouped reservations (one block per group) ═══ --}}
                                    @if(isset($reservationGroups) && count($reservationGroups) > 0)
                                        @foreach($reservationGroups as $gid => $gData)
                                            @php
                                                $grp = $gData['group'];
                                                $grpCustomer = $gData['customer'];
                                                $grpReservations = $gData['reservations'];
                                                $grpTotalDue = (float) $grp->total_due;
                                                $grpPaid = (float) $grp->activeEntries()->sum('amount');
                                                $grpRemaining = max(0, round($grpTotalDue - $grpPaid, 2));
                                                $grpDogNames = collect($grpReservations)->map(fn($r) => $r->dog?->name ?? '?')->implode(', ');
                                            @endphp
                                            <tr class="customer-group-header" style="background-color: #dbeafe;">
                                                <td colspan="6">
                                                    <i class="mdi mdi-account-group me-1"></i>
                                                    <strong>Gruppe G-{{ $grp->id }}: {{ $grpCustomer ? $grpCustomer->name : 'Kein Kunde' }}</strong>
                                                    <span class="text-muted ms-2">({{ count($grpReservations) }} Hunde: {{ $grpDogNames }})</span>
                                                </td>
                                                <td colspan="2" class="text-end fw-bold">
                                                    Gesamt: {{ number_format($grpTotalDue, 2, ',', '.') }}€
                                                    <br><small class="text-success">Bezahlt: {{ number_format($grpPaid, 2, ',', '.') }}€</small>
                                                    <br><small class="{{ $grpRemaining > 0.01 ? 'text-danger' : 'text-success' }}">Rest: {{ number_format($grpRemaining, 2, ',', '.') }}€</small>
                                                </td>
                                                <td colspan="4" class="text-end">
                                                    @if($grpRemaining > 0.01)
                                                    <form action="{{ route('admin.dogs.rooms.group-checkout') }}" method="POST" class="d-inline-flex align-items-center gap-2" onsubmit="this.querySelector('button').disabled=true;">
                                                        @csrf
                                                        <input type="hidden" name="group_id" value="{{ $grp->id }}">
                                                        <select name="gateway" class="form-control form-control-sm" style="width:auto;">
                                                            <option value="Bar">Bar</option>
                                                            <option value="Bank">Bank</option>
                                                        </select>
                                                        <input type="number" name="received_amount" step="0.01" value="{{ number_format($grpRemaining, 2, '.', '') }}" class="form-control form-control-sm" style="width:110px;">
                                                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                                                            <i class="mdi mdi-check-all me-1"></i>Gruppen-Checkout
                                                        </button>
                                                    </form>
                                                    @else
                                                    <form action="{{ route('admin.dogs.rooms.group-checkout') }}" method="POST" class="d-inline" onsubmit="this.querySelector('button').disabled=true;">
                                                        @csrf
                                                        <input type="hidden" name="group_id" value="{{ $grp->id }}">
                                                        <input type="hidden" name="received_amount" value="0">
                                                        <input type="hidden" name="gateway" value="Bar">
                                                        <button type="submit" class="btn btn-sm btn-success text-nowrap">
                                                            <i class="mdi mdi-check-all me-1"></i>Gruppen-Checkout (bezahlt)
                                                        </button>
                                                    </form>
                                                    @endif
                                                </td>
                                            </tr>
                                            @foreach($grpReservations as $obj)
                                                @php
                                                    $checkinDate = \Carbon\Carbon::parse($obj->checkin_date)->startOfDay();
                                                    $now = \Carbon\Carbon::now()->startOfDay();
                                                    $daysDiff = $checkinDate->diffInDays($now);
                                                    $days_between = ($daysDiff === 0) ? 1 : ((config('app.days_calculation_mode','inclusive') === 'inclusive') ? $daysDiff + 1 : $daysDiff);
                                                    $plan_title = $obj->plan?->title ?? '';
                                                    $plan_price = $obj->plan?->price ?? 0;
                                                    $basePrice = (double)$plan_price * (int)$days_between;
                                                    if ($vatMode === 'inclusive') { $g = $basePrice; } else { $g = $basePrice * (1 + ($vatPercentage / 100)); }
                                                    $total += $g;
                                                @endphp
                                                <tr style="background-color:#f0f7ff;">
                                                    <td>{{ $obj->dog->id }}</td>
                                                    <td>{{ $obj->dog->name }}</td>
                                                    <td>{{ $obj->dog->customer->name ?? 'N/A' }}</td>
                                                    <td>({{ $days_between }} Tage)</td>
                                                    <td>
                                                        <div class="plan-tag">{{ $plan_title ?: 'Kein Plan' }}</div>
                                                        <div class="plan-price-label">€{{ number_format($plan_price, 2, ',', '.') }} / Tag</div>
                                                    </td>
                                                    <td>—</td>
                                                    <td>—</td>
                                                    <td class="fw-bold">{{ number_format($g, 2, ',', '.') }}€</td>
                                                    <td colspan="4" class="text-muted small">via Gruppe</td>
                                                </tr>
                                            @endforeach
                                        @endforeach

                                        {{-- Separator --}}
                                        @if(count($groupedReservations ?? []) > 0)
                                        <tr><td colspan="12" style="border-top:3px solid #d0d8e4; padding:6px 0;"><strong class="text-muted">Einzelreservierungen</strong></td></tr>
                                        @endif
                                    @endif

                                    {{-- ═══ Ungrouped reservations (existing per-dog rows) ═══ --}}
                                    @if(isset($groupedReservations))
                                        @foreach($groupedReservations as $customerId => $group)
                                            @php
                                                $customer = $group['customer'];
                                                $customerReservations = $group['reservations'];
                                            @endphp

                                            <tr class="customer-group-header">
                                                <td colspan="8">
                                                    <strong>{{ $customer ? $customer->name . ' (ID: ' . $customer->id . ')' : 'Kein Kunde' }}</strong>
                                                </td>
                                                <td colspan="4" class="text-end">
                                                    <div class="form-check mt-1 d-inline-block">
                                                        <input class="form-check-input" type="checkbox" name="send_to_hellocash[{{ $customerId }}]" id="send_to_hellocash_{{ $customerId }}" value="1">
                                                        <label class="form-check-label" for="send_to_hellocash_{{ $customerId }}">
                                                            <i class="bx bx-receipt me-1"></i>Registrierkasse (nur Bar)
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>

                                            @foreach($customerReservations as $obj)
                                            @php
                                                $checkinDate = \Carbon\Carbon::parse($obj->checkin_date)->startOfDay();
                                                $now = \Carbon\Carbon::now()->startOfDay();
                                                $daysDiff = $checkinDate->diffInDays($now);

                                                if ($daysDiff === 0) {
                                                    $days_between = 1;
                                                } else {
                                                    $calculationMode = config('app.days_calculation_mode', 'inclusive');
                                                    $days_between = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                                                }

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
                                                $advancePaid = $obj->total_paid ?? 0;
                                                $remaining = max(0, $initial_gross - $advancePaid);
                                            @endphp
                                            <tr data-customer-id="{{ $customerId }}">
                                                <td>{{$obj->dog->id}}</td>
                                                <td>{{$obj->dog->name}}</td>
                                                <td>{{$obj->dog->customer->name ?? 'N/A'}} ({{$obj->dog->customer->id ?? 'N/A'}})</td>
                                                <td>({{$days_between}} Tage)</td>
                                                <td>
                                                    <div class="plan-tag">{{ $plan_title ?: 'Kein Plan' }}</div>
                                                    <div class="plan-price-label">€{{ number_format($plan_price, 2, ',', '.') }} / Tag</div>
                                                    <input type="hidden" name="plan_id[]" value="{{ $current_plan_id }}">
                                                </td>
                                                <td>
                                                    <select required class="form-control" id="discount_select{{$rowIndex}}" onchange="updateDiscount('{{$rowIndex}}')">
                                                        <option selected value="0">0%</option>
                                                        <option value="10">10%</option>
                                                        <option value="15">15%</option>
                                                    </select>
                                                    <input type="hidden" name="discount[]" id="discount{{$rowIndex}}" value="0">
                                                </td>
                                                <td>
                                                    <select multiple class="form-control additional-cost-select" id="additional_cost_select{{$rowIndex}}" name="additional_costs[{{$rowIndex}}][]" data-row="{{$rowIndex}}" data-placeholder="Zusatzkosten wählen...">
                                                        @foreach($additionalCosts as $cost)
                                                            <option value="{{$cost->id}}" data-price="{{$cost->price}}">{{$cost->title}}</option>
                                                        @endforeach
                                                    </select>
                                                    <div id="additional_cost_inputs{{$rowIndex}}" class="mt-2 additional-cost-input"></div>
                                                </td>
                                                <td>
                                                    <div style="margin-bottom: 4px;">
                                                        <input required type="number" step="0.10" class="form-control" id="invoice_amount{{$rowIndex}}" name="invoice_amount[]" value="{{ number_format($initial_gross, 2, '.', '')}}">
                                                    </div>
                                                    <small id="vat_info{{$rowIndex}}" class="vat-info-text">Netto: {{ number_format($initial_net, 2, ',', '.') }}€ + MwSt: {{ number_format($initial_vat, 2, ',', '.') }}€</small>
                                                </td>
                                                <td>
                                                    <span id="advance_paid_display{{$rowIndex}}">{{ number_format($advancePaid, 2, ',', '.') }}</span>€
                                                    <input type="hidden" id="advance_paid{{$rowIndex}}" value="{{ number_format($advancePaid, 2, '.', '') }}">
                                                </td>
                                                <td>
                                                    <span id="remaining_display{{$rowIndex}}">{{ number_format($remaining, 2, ',', '.') }}</span>€
                                                    <input type="hidden" id="remaining_value{{$rowIndex}}" value="{{ number_format($remaining, 2, '.', '') }}">
                                                </td>
                                                <td>
                                                    <select class="form-control" name="payment_mode[]" id="payment_mode{{$rowIndex}}" onchange="toggleRowPaymentMode('{{$rowIndex}}')">
                                                        <option selected value="single">Einfach</option>
                                                        <option value="split">Split</option>
                                                    </select>
                                                    <div class="mt-2" id="payment_method_wrapper{{$rowIndex}}">
                                                        <select class="form-control" name="payment_method[]" id="payment_method{{$rowIndex}}">
                                                            <option selected value="Bar">Bar</option>
                                                            <option value="Bank">Banküberweisung</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div id="payment_amount_single{{$rowIndex}}">
                                                        <span class="amount-received-display" id="payment_amount_display{{$rowIndex}}">{{ number_format($remaining, 2, ',', '.') }}€</span>
                                                        <input type="hidden" id="payment_amount{{$rowIndex}}" name="payment_amount[]" value="{{ number_format($remaining, 2, '.', '')}}">
                                                    </div>
                                                    <div id="payment_amount_split{{$rowIndex}}" style="display:none;">
                                                        <input readonly type="number" step="0.01" class="form-control mb-2 readonly-amount" id="payment_amount_cash{{$rowIndex}}" name="payment_amount_cash[]" placeholder="Bar">
                                                        <input readonly type="number" step="0.01" class="form-control readonly-amount" id="payment_amount_bank{{$rowIndex}}" name="payment_amount_bank[]" placeholder="Bank">
                                                    </div>
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
@endif
@endsection
@section('extra_js')
@if(!empty($bulkCheckoutOnly))
@else
<script>
    var vatPercentage = {{ $vatPercentage ?? 20 }};
    var vatMode = '{{ config("app.vat_calculation_mode", "exclusive") }}';
    var manuallyEditedInvoice = {};

    function getNetFromGross(grossAmount) {
        var net = grossAmount / (1 + (vatPercentage / 100));
        return parseFloat(net.toFixed(2));
    }

    function calculateVATFromNet(netAmount) {
        return parseFloat((netAmount * (vatPercentage / 100)).toFixed(2));
    }

    function updateDiscount(index) {
        var discountSelect = $("#discount_select" + index);
        var discountValue = parseInt(discountSelect.val()) || 0;
        $("#discount" + index).val(discountValue);
        recalculateRow(index);
    }

    function updateAdditionalCosts(index) {
        var select = $("#additional_cost_select" + index);
        var container = $("#additional_cost_inputs" + index);
        var existingValues = {};
        container.find('input').each(function() {
            var name = $(this).attr('name') || '';
            var match = name.match(/additional_cost_values\[\d+\]\[(\d+)\]/);
            if (match) {
                existingValues[match[1]] = $(this).val();
            }
        });
        container.empty();

        select.find('option:selected').each(function() {
            var costId = $(this).val();
            var price = parseFloat($(this).data('price')) || 0;
            var selectedPrice = existingValues[costId];
            if (selectedPrice === undefined || selectedPrice === null || selectedPrice === '') {
                selectedPrice = $(this).data('selected-price');
            }
            if (selectedPrice === undefined || selectedPrice === null || selectedPrice === '') {
                selectedPrice = price;
            }
            selectedPrice = parseFloat(selectedPrice) || 0;
            var title = $(this).text();

            var input = '<div class="input-group input-group-sm mb-1">'
                + '<span class="input-group-text">' + title + '</span>'
                + '<input type="number" step="0.01" class="form-control additional-cost-input" '
                + 'name="additional_cost_values[' + index + '][' + costId + ']" value="' + selectedPrice.toFixed(2) + '" />'
                + '</div>';
            container.append(input);
        });

        recalculateRow(index);
    }

    function sumAdditionalCosts(index) {
        var total = 0;
        $("#additional_cost_inputs" + index + " input").each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        return total;
    }

    function toggleRowPaymentMode(index) {
        var mode = $("#payment_mode" + index).val();
        if (mode === 'split') {
            $("#payment_amount_single" + index).hide();
            $("#payment_amount_split" + index).show();
            $("#payment_method_wrapper" + index).hide();

            var remaining = parseFloat($("#remaining_value" + index).val()) || 0;
            $("#payment_amount_cash" + index).val(remaining.toFixed(2));
            $("#payment_amount_bank" + index).val('0.00');
        } else {
            $("#payment_amount_single" + index).show();
            $("#payment_amount_split" + index).hide();
            $("#payment_method_wrapper" + index).show();
        }
    }

    function recalculateRow(index)
    {
        var baseCost = parseFloat($("#base_cost" + index).val()) || 0;
        var additionalTotal = sumAdditionalCosts(index);
        var discount = parseInt($("#discount" + index).val()) || 0;

        var netTotal;
        if (vatMode === 'inclusive') {
            netTotal = getNetFromGross(baseCost + additionalTotal);
        } else {
            netTotal = baseCost + additionalTotal;
        }

        if (discount > 0) {
            netTotal = netTotal * (1 - (discount / 100));
        }
        netTotal = parseFloat(netTotal.toFixed(2));

        var vatAmount = calculateVATFromNet(netTotal);
        var grossTotal = parseFloat((netTotal + vatAmount).toFixed(2));

        if (manuallyEditedInvoice[index]) {
            grossTotal = parseFloat($("#invoice_amount" + index).val()) || 0;
            netTotal = getNetFromGross(grossTotal);
            vatAmount = parseFloat((grossTotal - netTotal).toFixed(2));
        }

        $("#net_amount" + index).val(netTotal.toFixed(2));
        $("#vat_amount_row" + index).val(vatAmount.toFixed(2));
        if (!manuallyEditedInvoice[index]) {
            $("#invoice_amount" + index).val(grossTotal.toFixed(2));
        }
        $("#vat_info" + index).html("Netto: " + netTotal.toFixed(2) + "€ + MwSt: " + vatAmount.toFixed(2) + "€");

        var advancePaid = parseFloat($("#advance_paid" + index).val()) || 0;
        var remaining = grossTotal - advancePaid;
        if (remaining < 0) {
            remaining = 0;
        }
        $("#remaining_value" + index).val(remaining.toFixed(2));
        $("#remaining_display" + index).text(remaining.toFixed(2).replace('.', ',') + '€');

        $("#payment_amount" + index).val(remaining.toFixed(2));
        var displayVal = remaining.toFixed(2).replace('.', ',') + '€';
        $("#payment_amount_display" + index).text(displayVal);

        toggleRowPaymentMode(index);
        updateVATTotals();
        updateGrandTotal();
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

    function updateGrandTotal()
    {
        var totalGross = 0;
        $("input[id^='invoice_amount']").each(function() {
            totalGross += parseFloat($(this).val()) || 0;
        });
        $("#totalAmount").text(totalGross.toFixed(2).replace('.', ',') + "€");
    }

    function initPage() {
        $('.additional-cost-select').each(function() {
            var index = $(this).data('row');
            $(this).val(null).trigger('change');
            $('#additional_cost_inputs' + index).empty();
            recalculateRow(index);
        });
    }

    $(document).ready(function(){
        $('.additional-cost-select').each(function() {
            var $sel = $(this);
            var index = $sel.data('row');
            $sel.select2({
                width: '100%',
                placeholder: $sel.data('placeholder'),
                allowClear: true,
                closeOnSelect: false
            });
            $sel.on('change', function() {
                updateAdditionalCosts(index);
            });
        });

        initPage();

        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                initPage();
            }
        });

        $(document).on('input', "input[id^='invoice_amount']", function() {
            var index = $(this).attr('id').replace('invoice_amount', '');
            manuallyEditedInvoice[index] = true;
            recalculateRow(index);
        });

        $(document).on('input', '.additional-cost-input', function() {
            var name = $(this).attr('name');
            var match = name.match(/additional_cost_values\[(\d+)\]/);
            if (match) {
                recalculateRow(match[1]);
            }
        });
    });
</script>
<script src="assets/vendor/libs/select2/select2.js"></script>
@endif
@endsection
