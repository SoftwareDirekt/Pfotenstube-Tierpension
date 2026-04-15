@extends('admin.layouts.app')
@section('title')
    <title>{{ $customer->name }}</title>
@endsection
@section('extra_css')
<style>
  .table.datatable-project th,
  .table.datatable-project td {
      white-space: nowrap;
  }

  .table-responsive {
      overflow-x: auto;
  }

  /* Gruppen-Zahlungszeilen in Kundenzahlungen (unterscheidet von Einzel-Reservierung) */
  tr.payment-row-group > td {
      background-color: #e8f2fb !important;
  }
  tr.payment-row-group:hover > td {
      background-color: #dceaf8 !important;
  }
</style>
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row">
      <!-- User Sidebar -->
      <div class="col-xl-12 col-lg-12 col-md-12 order-1 order-md-0">
        <!-- User Card -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="row">
              <div class="col-md-2">
                <div class="user-avatar-section">
                  <div class="text-center">
                    @if($customer->picture == null)
                    <img
                      class="img-fluid rounded mb-3 mt-4"
                      src="assets/img/avatars/10.png"
                      height="120"
                      width="120"
                      alt="User avatar" />
                    @else
                    <img
                      class="img-fluid rounded mb-3 mt-4"
                      src="uploads/users/{{$customer->picture}}"
                      height="120"
                      width="120"
                      alt="Profile Picture" />
                    @endif
                    <div class="user-info text-cente">
                      <h4 style="font-size: 20px"><?php echo $customer->name; ?></h4>
                      <span class="badge bg-label-secondary">
                        {{ $customer->type }}
                    </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-10">
                <h5 class="pb-3 border-bottom mt-3 mb-3">Einzelheiten</h5>
                <div class="info-container">
                  <div class="table-responsive">
                    <table class="table table-hover bluetd">
                      <tbody>
                        <tr>
                          <th>Typ</th>
                          <td>{{$customer->type}}</td>
                          <th>Anrede</th>
                          <td>{{$customer->title}}</td>
                          <th>Vollständiger Name</th>
                          <td>{{$customer->name}}</td>
                        </tr>
                        <tr>
                          <th>ID-Number</th>
                          <td>{{$customer->id_number}}</td>
                          <th>Organisation / Beruf</th>
                          <td>{{$customer->profession ?? '-'}}</td>
                          <th>Email</th>
                          <td>{{$customer->email}}</td>
                        </tr>
                        <tr>
                          <th>Telefonnummer</th>
                          <td>{{$customer->phone}}</td>
                          <th>Notfallkontakt</th>
                          <td>{{$customer->emergency_contact ?? '-'}}</td>
                          <th>Hauseigener Tierarzt</th>
                          <td>{{$customer->veterinarian ?? '-'}}</td>
                        </tr>
                        <tr>
                          <th>Straße</th>
                          <td>{{$customer->street ?? '-'}}</td>
                          <th>Stadt</th>
                          <td>{{$customer->city ?? '-'}}</td>
                          <th>PLZ</th>
                          <td>{{$customer->zipcode ?? '-'}}</td>
                        </tr>
                        <tr>
                          <th>Land</th>
                          <td>{{$customer->country ?? '-'}}</td>
                          <th>Kunde seit</th>
                          <td>{{date('d M, Y', strtotime($customer->created_at))}}</td>
                          <th></th>
                          <td></td>
                        </tr>

                      </tbody>
                    </table>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!--/ User Sidebar -->

      <!-- User Content -->
      <div class="col-xl-12 col-lg-12 col-md-12 order-0 order-md-1">
          <!-- /Project table -->
          <div class="card mb-4">
              <div class="row">
                  <div class="col-md-6">
                      <h5 class="card-header">Kunde Hund</h5>
                  </div>
                  <div class="col-md-6 d-flex justify-content-end align-items-center">
                      <div class="mx-4">
                          <a href="{{route('admin.customers.add_dog.view', ['id' => $customer->id])}}">
                              <i class="fa fa-plus"></i> &nbsp;Neuer Hund
                          </a>
                      </div>
                  </div>
              </div>
              <div class="table-responsive mb-3">
                  <table class="table datatable-project bluetd">
                      <thead class="table-light">
                      <tr>
                          <th></th>
                          <th>Name</th>
                          <th>Alter</th>
                          <th>Kastriert</th>
                          <th>Rasse</th>
                          <th>Chipnummer</th>
                          <th>Medikamente</th>
                          <th>Essgewohnheiten</th>
                          <th>Geschlecht</th>
                          <th class="text-end">Aktion</th>
                      </tr>
                      </thead>
                      <tbody class="table-border-bottom-0">
                      @if(count($dogs) > 0)
                      @foreach($dogs as $obj)
                          @php
                            $age = '';
                            if($obj->age)
                            {
                              $current_date = \Carbon\Carbon::now();

                              // $birthdate = \Carbon\Carbon::createFromFormat('Y-m-d', $obj->age);
                              // $age = date('d.m.Y', strtotime($birthdate));

                              $age = $obj->age;
                              // $age = $current_date->diffInDays($birthdate);
                            }

                            $kclass = '';
                            if($obj->status == 2)
                            {
                              $kclass = 'adoptedBG';
                            }elseif($obj->status == 3)
                            {
                              $kclass = 'deadBG';
                            }
                          @endphp
                          <tr class="{{$kclass}}">
                              <td><img src="uploads/users/dogs/{{$obj->picture}}" style="width: 6rem" alt="#"></td>
                              <td>{{$obj->name}}</td>
                              <td>{{$age}}</td>
                              <td>{{ $obj->neutered == 1 ? 'Ja' : 'Nein' }}</td>
                              <td>{{$obj->compatible_breed}}</td>

                              <td>{{$obj->chip_number}}</td>
                              <td>
                                @php
                                    if($obj->is_medication)
                                    {
                                        $morgen = $obj->morgen == '' ? 'x' : $obj->morgen;
                                        $mittag = $obj->mittag == '' ? 'x' : $obj->mittag;
                                        $abend = $obj->abend == '' ? 'x' : $obj->abend;
                                       echo "Morgen ($morgen) - Mittag ($mittag) - Abend ($abend)";
                                    }
                                @endphp
                              </td>
                              <td>
                                @php
                                    if($obj->is_eating_habits)
                                    {
                                        $morgen = $obj->eating_morning == '' ? 'x' : $obj->eating_morning;
                                        $mittag = $obj->eating_midday == '' ? 'x' : $obj->eating_midday;
                                        $abend = $obj->eating_evening == '' ? 'x' : $obj->eating_evening;
                                       echo "Morgen ($morgen) - Mittag ($mittag) - Abend ($abend)";
                                    }
                                @endphp
                              </td>
                              <td>{{$obj->gender}}</td>
                              @if($obj->status == 1)
                                  <td>
                                      <div class="d-flex justify-content-end">
                                          <div>
                                              <a href="{{route('admin.customers.edit_dog' , ['id' => $obj->id])}}">
                                                  <button class="no-style actionBtn">
                                                      <i class="fa fa-edit md-icon"></i>
                                                  </button>
                                              </a>
                                          </div>
                                          <div>
                                              <a href="{{route('admin.customers.edit_dog' , ['id' => $obj->id])}}">
                                                  <button class="no-style actionBtn">
                                                      <i class="fa fa-eye md-icon"></i>
                                                  </button>
                                              </a>
                                          </div>
                                      </div>
                                      <div class="d-flex justify-content-end">
                                          <div>
                                              <button class="no-style actionBtn" onclick="dogAdoption('{{$obj->id}}')">
                                                  <i class="fa fa-dog md-icon"></i>
                                              </button>
                                          </div>
                                          <div>
                                              <button class="no-style actionBtn" onclick="diedDog('{{$obj->id}}')">
                                                  <i class="fa fa-skull md-icon"></i>
                                              </button>
                                          </div>
                                      </div>
                                      <div class="d-flex justify-content-end">
                                          <div>
                                              <button class="no-style actionBtn" onclick="deleteCustomerDog('{{$obj->id}}')">
                                                  <i class="fa fa-trash"></i>
                                              </button>
                                          </div>
                                      </div>
                                  </td>
                              @endif
                          </tr>
                      @endforeach
                      @else
                          <tr class="text-center">
                            <td colspan="10">Keine Aufzeichnungen gefunden</td>
                          </tr>
                      @endif
                      </tbody>
                  </table>
              </div>
          </div>
        <!-- Project table -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Kundenzahlungen</h5>
            <div class="text-end fs-5">
              <span class="fw-semibold me-1">Kundensaldo:</span>
              @php
                $saldoClass = $customerBalance < 0 ? 'text-danger' : ($customerBalance > 0 ? 'text-success' : '');
                $saldoSign = $customerBalance > 0 ? '+' : '';
              @endphp
              <span class="{{ $saldoClass }}">{{ $saldoSign }}{{ number_format($customerBalance, 2) }}&euro;</span>
            </div>
          </div>
          <div class="table-responsive mb-3">
            <table class="table datatable-project bluetd">
              <thead class="table-light">
                <tr>
                  <th class="text-nowrap">Re. Nr.</th>
                  <th>Hund</th>
                  <th>Einchecken</th>
                  <th>Auschecken</th>
                  <th>Zahlungsart</th>
                  <th>Typ</th>
                  <th class="text-nowrap">Betrag (&euro;)</th>
                  <th class="text-nowrap">Gesamt (&euro;)</th>
                  <th class="text-nowrap">Restbetrag (&euro;)</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @if(count($payments) > 0)
                @foreach($payments as $pay)
                  @php
                    $isGroup = ($pay['source'] ?? '') === 'group';
                    $totalDue = isset($pay['total_due']) ? (float)$pay['total_due'] : 0.0;
                    $amount = isset($pay['amount']) ? (float)$pay['amount'] : 0.0;
                    $remaining = isset($pay['remaining']) ? (float)$pay['remaining'] : 0.0;
                    $remainingClass = $remaining > 0 ? 'text-danger' : 'text-success';
                    $groupBalanceTitle = 'Bei Gruppenzahlungen gilt der Betrag in dieser Zeile; Gesamt und Restbetrag der Gruppe ergeben sich aus allen Gruppenzeilen und sind hier nicht pro Zeile dargestellt.';
                  @endphp
                  <tr class="{{ $isGroup ? 'payment-row-group' : '' }}">
                    <td>
                      @if(!empty($pay['invoice_id']))
                        <a href="{{ route('admin.invoices.view', $pay['invoice_id']) }}" target="_blank">
                          {{ $pay['invoice_number'] }}
                        </a>
                      @else
                        {{ $pay['invoice_number'] }}
                      @endif
                    </td>
                    <td>
                      {{ $pay['dog'] }}
                      @if(!empty($pay['dog_id']))
                        ({{ $pay['dog_id'] }})
                      @endif
                    </td>
                    <td>{{ $pay['checkin'] ? date('d.m.Y', strtotime($pay['checkin'])) : '-' }}</td>
                    <td>{{ $pay['checkout'] ? date('d.m.Y', strtotime($pay['checkout'])) : '-' }}</td>
                    <td>{{$pay['method']}}</td>
                    <td>
                      @if($isGroup)
                        <span class="badge rounded-pill bg-label-primary me-1">Gruppe</span>
                      @endif
                      {{ ($pay['entry_type'] ?? '') === 'advance' ? 'Anzahlung' : 'Zahlung' }}
                    </td>
                    <td>{{ number_format($amount, 2) }}&euro;</td>
                    <td class="text-muted">
                      @if($isGroup)
                        <span class="user-select-none" title="{{ $groupBalanceTitle }}">&mdash;</span>
                      @else
                        {{ number_format($totalDue, 2) }}&euro;
                      @endif
                    </td>
                    <td>
                      @if($isGroup)
                        <span class="text-muted user-select-none" title="{{ $groupBalanceTitle }}">&mdash;</span>
                      @else
                        <span class="{{ $remainingClass }}">{{ number_format($remaining, 2) }}&euro;</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $status = $pay['status'] ?? 'unpaid';
                      @endphp
                      @if($status === 'paid')
                        <span class="badge bg-success">Bezahlt</span>
                      @elseif($status === 'partial')
                        <span class="badge bg-info">Teilweise</span>
                      @else
                        <span class="badge bg-danger">Nicht Bezahlt</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
                @else
                <tr>
                  <td colspan="10" class="text-center">Keine Aufzeichnungen gefunden</td>
                </tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>

      </div>
      <!--/ User Content -->
  </div>

  {{-- Modals Starts here --}}

  {{-- Edit User Modal --}}
  <div class="modal fade" id="editUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-edit-user">
          <div class="modal-content p-3 p-md-5">
            <div class="modal-body py-3 py-md-0">
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              <div class="text-center mb-4">
                <h3 class="mb-2">Edit User Information</h3>
                <p class="pt-1">Updating user details will receive a privacy audit.</p>
              </div>
              <form id="editUserForm" class="row g-4" onsubmit="return false">
                <div class="col-12 col-md-6">
                  <div class="form-floating form-floating-outline">
                    <input
                      type="text"
                      id="modalEditUserFirstName"
                      name="modalEditUserFirstName"
                      class="form-control"
                      placeholder="John" />
                    <label for="modalEditUserFirstName">First Name</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating form-floating-outline">
                    <input
                      type="text"
                      id="modalEditUserLastName"
                      name="modalEditUserLastName"
                      class="form-control"
                      placeholder="Doe" />
                    <label for="modalEditUserLastName">Last Name</label>
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-floating form-floating-outline">
                    <input
                      type="text"
                      id="modalEditUserName"
                      name="modalEditUserName"
                      class="form-control"
                      placeholder="john.doe.007" />
                    <label for="modalEditUserName">Username</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating form-floating-outline">
                    <input
                      type="text"
                      id="modalEditUserEmail"
                      name="modalEditUserEmail"
                      class="form-control"
                      placeholder="example@domain.com" />
                    <label for="modalEditUserEmail">Email</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating form-floating-outline">
                    <select
                      id="modalEditUserStatus"
                      name="modalEditUserStatus"
                      class="form-select"
                      aria-label="Default select example">
                      <option selected>Status</option>
                      <option value="1">Active</option>
                      <option value="2">Inactive</option>
                      <option value="3">Suspended</option>
                    </select>
                    <label for="modalEditUserStatus">Status</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating form-floating-outline">
                    <input
                      type="text"
                      id="modalEditTaxID"
                      name="modalEditTaxID"
                      class="form-control modal-edit-tax-id"
                      placeholder="123 456 7890" />
                    <label for="modalEditTaxID">Tax ID</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="input-group input-group-merge">
                    <span class="input-group-text">US (+1)</span>
                    <div class="form-floating form-floating-outline">
                      <input
                        type="text"
                        id="modalEditUserPhone"
                        name="modalEditUserPhone"
                        class="form-control phone-number-mask"
                        placeholder="202 555 0111" />
                      <label for="modalEditUserPhone">Phone Number</label>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating form-floating-outline">
                    <select
                      id="modalEditUserLanguage"
                      name="modalEditUserLanguage"
                      class="select2 form-select"
                      multiple>
                      <option value="">Select</option>
                      <option value="english" selected>English</option>
                      <option value="spanish">Spanish</option>
                      <option value="french">French</option>
                      <option value="german">German</option>
                      <option value="dutch">Dutch</option>
                      <option value="hebrew">Hebrew</option>
                      <option value="sanskrit">Sanskrit</option>
                      <option value="hindi">Hindi</option>
                    </select>
                    <label for="modalEditUserLanguage">Language</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating form-floating-outline">
                    <select
                      id="modalEditUserCountry"
                      name="modalEditUserCountry"
                      class="select2 form-select"
                      data-allow-clear="true">
                      <option value="">Select</option>
                      <option value="Australia">Australia</option>
                      <option value="Bangladesh">Bangladesh</option>
                      <option value="Belarus">Belarus</option>
                      <option value="Brazil">Brazil</option>
                      <option value="Canada">Canada</option>
                      <option value="China">China</option>
                      <option value="France">France</option>
                      <option value="Germany">Germany</option>
                      <option value="India">India</option>
                      <option value="Indonesia">Indonesia</option>
                      <option value="Israel">Israel</option>
                      <option value="Italy">Italy</option>
                      <option value="Japan">Japan</option>
                      <option value="Korea">Korea, Republic of</option>
                      <option value="Mexico">Mexico</option>
                      <option value="Philippines">Philippines</option>
                      <option value="Russia">Russian Federation</option>
                      <option value="South Africa">South Africa</option>
                      <option value="Thailand">Thailand</option>
                      <option value="Turkey">Turkey</option>
                      <option value="Ukraine">Ukraine</option>
                      <option value="United Arab Emirates">United Arab Emirates</option>
                      <option value="United Kingdom">United Kingdom</option>
                      <option value="United States">United States</option>
                    </select>
                    <label for="modalEditUserCountry">Country</label>
                  </div>
                </div>
                <div class="col-12">
                  <label class="switch">
                    <input type="checkbox" class="switch-input" />
                    <span class="switch-toggle-slider">
                      <span class="switch-on"></span>
                      <span class="switch-off"></span>
                    </span>
                    <span class="switch-label">Als Rechnungsadresse verwenden?</span>
                  </label>
                </div>
                <div class="col-12 text-center">
                  <button type="submit" class="btn btn-primary me-sm-3 me-1">Speichern</button>
                  <button
                    type="reset"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                    aria-label="Schließen">
                    Abbrechen
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
  </div>

  {{-- Add New Credit Card Modal --}}
  <div class="modal fade" id="upgradePlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple modal-upgrade-plan">
          <div class="modal-content p-3 p-md-5">
            <div class="modal-body pt-md-0 px-0">
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              <div class="text-center mb-4">
                <h3 class="mb-2 pb-1">Upgrade Plan</h3>
                <p>Choose the best plan for user.</p>
              </div>
              <form id="upgradePlanForm" class="row g-3 d-flex align-items-center" onsubmit="return false">
                <div class="col-sm-9">
                  <div class="form-floating form-floating-outline">
                    <select id="choosePlan" name="choosePlan" class="form-select" aria-label="Choose Plan">
                      <option selected>Choose Plan</option>
                      <option value="standard">Standard - $99/month</option>
                      <option value="exclusive">Exclusive - $249/month</option>
                      <option value="Enterprise">Enterprise - $499/month</option>
                    </select>
                    <label for="choosePlan">Choose Plan</label>
                  </div>
                </div>
                <div class="col-sm-3 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary">Upgrade</button>
                </div>
              </form>
            </div>
            <hr class="mx-md-n5 mx-n3" />
            <div class="modal-body pb-md-0 px-0">
              <h6 class="mb-0">User current plan is standard plan</h6>
              <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex justify-content-center me-2 mt-3">
                  <sup class="h5 pricing-currency pt-1 mt-3 mb-0 me-1 text-primary">$</sup>
                  <h1 class="fw-bold display-3 mb-0 text-primary">99</h1>
                  <sub class="h5 pricing-duration mt-auto mb-2">/month</sub>
                </div>
                <button class="btn btn-label-danger cancel-subscription mt-3">Cancel Subscription</button>
              </div>
            </div>
          </div>
        </div>
  </div>

  {{-- Delete Customer Dog Modal --}}
  <div class="modal fade" id="deleteCustomerDog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Hund löschen</h3>
            <hr>
          </div>
          <p class="text-center">Möchten Sie diesen Hund wirklich löschen?</p>
          <form class="row g-2" method="POST" action="{{route('admin.customers.delete_dog')}}">
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

  {{-- Dog Adoption Date Modal  --}}
  <div class="modal fade" id="dogAdoption" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Adoptionsdatum des Hundes</h3>
            <hr>
          </div>
          <form class="row g-3" method="POST" action="{{route("admin.customers.update_dog_adoption")}}">
            @csrf
            <div class="col-12">
                <div class="form-floating form-floating-outline">
                  <input
                    type="date"
                    id="dog_adoption"
                    name="adoption_date"
                    class="form-control"/>
                  <label for="dog_adoption">Adoption Datum</label>
                </div>
            </div>
            <div class="col-12">
              <input type="hidden" name="id" id="id">
              <button type="submit" class="btn btn-primary me-1">Aktualisieren</button>
              <button
                type="reset"
                class="btn btn-outline-secondary"
                data-bs-dismiss="modal"
                aria-label="Close">
                Schließen
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Deceased Dogs Modal  --}}
  <div class="modal fade" id="diedDog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-simple modal-enable-otp modal-dialog-centered">
      <div class="modal-content p-3 p-md-5">
        <div class="modal-body p-md-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-4">
            <h3 class="mb-2 pb-1">Verstorbener Hund</h3>
            <hr>
          </div>
          <form class="row g-3" method="POST" action="{{route('admin.customers.update_dog_death')}}">
            @csrf
            <div class="col-12">
                <div class="form-floating form-floating-outline">
                  <input
                    type="date"
                    id="dog_death"
                    name="date_of_death"
                    class="form-control"/>
                  <label for="dog_death">Sterbedatum</label>
                </div>
            </div>
            <div class="col-12">
              <input type="hidden" id="id" name="id">
              <button type="submit" class="btn btn-primary me-1">Aktualisieren</button>
              <button
                type="reset"
                class="btn btn-outline-secondary"
                data-bs-dismiss="modal"
                aria-label="Close">
                Schließen
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Modals Ends here --}}

</div>
@endsection
@section('extra_js')

<script>
    function deleteCustomerDog(id)
    {
        $("#deleteCustomerDog #id").val(id);
        $("#deleteCustomerDog").modal('show');
    }

    function dogAdoption(id)
    {
        $("#dogAdoption #id").val(id);
        $("#dogAdoption").modal('show');
    }

    function diedDog(id)
    {
        $("#diedDog #id").val(id);
        $("#diedDog").modal('show');
    }

</script>
@endsection
