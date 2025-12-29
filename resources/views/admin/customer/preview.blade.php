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
                  <th class="text-nowrap">Planpreis (&euro;)</th>
                  <th class="text-nowrap">Zusatzkosten (&euro;)</th>
                  <th>Rabatt</th>
                  <th>MwSt. (&euro;)</th>
                  <th>Rechnungsbetrag (&euro;)</th>
                  <th class="text-nowrap">Betrag erhalten (&euro;)</th>
                  <th class="text-nowrap">Restbetrag (&euro;)</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @if(count($payments) > 0)
                @foreach($payments as $pay)
                  @php
                    $planCost = isset($pay['plan_cost']) ? (float)$pay['plan_cost'] : 0.0;
                    $specialCost = isset($pay['special_cost']) ? (float)$pay['special_cost'] : 0.0;
                    $invoiceTotal = isset($pay['cost']) ? (float)$pay['cost'] : 0.0;
                    $vatAmount = isset($pay['vat_amount']) ? (float)$pay['vat_amount'] : 0.0;
                    $receivedAmount = isset($pay['received_amount']) ? (float)$pay['received_amount'] : 0.0;
                    $discountPercent = isset($pay['discount']) ? (float)$pay['discount'] : 0.0;
                    $discountAmount = isset($pay['discount_amount']) ? (float)$pay['discount_amount'] : 0.0;
                    
                    // Use effective remaining (accounts for settlements)
                    $originalRemaining = isset($pay['original_remaining']) ? (float)$pay['original_remaining'] : (isset($pay['remaining_amount']) ? (float)$pay['remaining_amount'] : ($invoiceTotal - $receivedAmount));
                    $effectiveRemaining = isset($pay['effective_remaining']) ? (float)$pay['effective_remaining'] : $originalRemaining;
                    $remainingClass = $effectiveRemaining > 0 ? 'text-danger' : 'text-success';
                  @endphp
                  <tr>
                    <td>{{$pay['id']}}</td>
                    <td>{{$pay['dog']}}({{$pay['dog_id']}})</td>
                    <td>{{ date('d.m.Y', strtotime($pay['checkin'])) }}</td>
                    <td>{{date('d.m.Y', strtotime($pay['checkout']))}}</td>
                    <td>{{$pay['type']}}</td>
                    <td>{{ number_format($planCost, 2) }}&euro;</td>
                    <td>{{ number_format($specialCost, 2) }}&euro;</td>
                    <td>{{ number_format($discountPercent, 0) }}% / {{ number_format($discountAmount, 2) }}&euro;</td>
                    <td>{{ number_format($vatAmount, 2) }}&euro;</td>
                    <td>{{ number_format($invoiceTotal, 2) }}&euro;</td>
                    <td>{{ number_format($receivedAmount, 2) }}&euro;</td>
                    <td>
                        <span class="{{ $remainingClass }}">{{ number_format($effectiveRemaining, 2) }}&euro;</span>
                        @if($effectiveRemaining < $originalRemaining)
                            <small class="text-muted d-block" style="cursor: pointer;" onclick="showSettlementDetails({{ $pay['id'] }})" title="Klicken Sie, um die Details der Begleichung anzuzeigen">
                                ({{ number_format($originalRemaining - $effectiveRemaining, 2) }}€ beglichen)
                            </small>
                        @endif
                    </td>
                    <td>
                      @php
                          // Determine status: if invoice is 0, it's paid; if effective remaining is 0, it's paid
                          $displayStatus = $pay['status'] ?? 0;
                          if ($invoiceTotal < 0.01) {
                              // Invoice amount is 0 (e.g., organization plan), automatically paid
                              $displayStatus = 1;
                          } elseif ($effectiveRemaining < 0.01) {
                              // Effective remaining is 0 (fully settled), automatically paid
                              $displayStatus = 1;
                          }
                      @endphp
                      @if($displayStatus == 0)
                      <span class="badge bg-danger">Nicht Bezahlt</span>
                      @elseif($displayStatus == 1)
                      <span class="badge bg-success">Bezahlt</span>
                      @elseif($displayStatus == 2)
                      <span class="badge bg-info">Offen</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="12" class="text-center">Keine Aufzeichnungen gefunden</td>
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
                    <span class="switch-label">Use as a billing address?</span>
                  </label>
                </div>
                <div class="col-12 text-center">
                  <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                  <button
                    type="reset"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                    Cancel
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

{{-- Settlement Details Modal --}}
<div class="modal fade" id="settlementDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title fw-bold">
                    <i class="bx bx-receipt me-2"></i>Begleichungsdetails
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f5f5f9;">
                <div id="settlementDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Laden...</span>
                        </div>
                        <p class="mt-3 text-muted">Lade Begleichungsdetails...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Schließen
                </button>
            </div>
        </div>
    </div>
</div>

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

    function showSettlementDetails(paymentId) {
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('settlementDetailsModal'));
        modal.show();
        
        // Reset content
        $('#settlementDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Laden...</span></div><p class="mt-3 text-muted">Lade Begleichungsdetails...</p></div>');
        
        // Fetch settlement details
        $.ajax({
            url: "{{ route('admin.payment.settlement.details', ['id' => ':id']) }}".replace(':id', paymentId),
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            success: function(data) {
                var html = '<div class="row g-4">';
                
                // Payment Information Card
                html += '<div class="col-12">';
                html += '<div class="card shadow-sm border-0">';
                html += '<div class="card-header bg-white border-bottom py-3">';
                html += '<h5 class="mb-0 fw-bold text-primary"><i class="bx bx-info-circle me-2"></i>Zahlungsinformationen</h5>';
                html += '</div>';
                html += '<div class="card-body p-4">';
                html += '<div class="row g-3">';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Zahlungs-ID</small><strong class="fs-5">#' + data.payment.id + '</strong></div></div></div>';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Hund</small><strong class="fs-6">' + data.payment.dog_name + '</strong></div></div></div>';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Kunde</small><strong class="fs-6">' + data.payment.customer_name + '</strong></div></div></div>';
                html += '<div class="col-md-6"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Erstellt am</small><strong class="fs-6">' + data.payment.created_at + '</strong></div></div></div>';
                html += '<div class="col-md-4"><div class="d-flex align-items-center p-3 bg-light rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Rechnungsbetrag</small><strong class="fs-5 text-dark">' + data.payment.invoice_total + '€</strong></div></div></div>';
                html += '<div class="col-md-4"><div class="d-flex align-items-center p-3 bg-light border border-danger rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Ursprünglicher Restbetrag</small><strong class="fs-5 text-danger">' + data.payment.original_remaining + '€</strong></div></div></div>';
                html += '<div class="col-md-4"><div class="d-flex align-items-center p-3 bg-light ' + (parseFloat(data.payment.effective_remaining) > 0 ? 'border border-danger' : 'border border-success') + ' rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Aktueller Restbetrag</small><strong class="fs-5 ' + (parseFloat(data.payment.effective_remaining) > 0 ? 'text-danger' : 'text-success') + '">' + data.payment.effective_remaining + '€</strong></div></div></div>';
                html += '<div class="col-12"><div class="d-flex align-items-center p-3 bg-light border border-success rounded"><div class="flex-grow-1"><small class="text-muted d-block mb-1">Gesamt beglichen</small><strong class="fs-4 text-success">' + data.payment.total_settled + '€</strong></div></div></div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                
                if (data.settlement_trail && data.settlement_trail.length > 0) {
                    // Summary Card
                    var paymentIds = data.settlement_trail.map(s => '#' + s.settling_payment_id).join(', ');
                    html += '<div class="col-12">';
                    html += '<div class="alert alert-info border-0 shadow-sm mb-0">';
                    html += '<div class="d-flex align-items-start">';
                    html += '<i class="bx bx-info-circle fs-4 me-3 mt-1"></i>';
                    html += '<div class="flex-grow-1">';
                    html += '<h6 class="alert-heading mb-2 fw-bold">Zusammenfassung</h6>';
                    html += '<p class="mb-0">Diese ursprüngliche Schuld von <strong class="text-danger">' + data.payment.original_remaining + '€</strong> wurde von <strong>' + data.settlement_trail.length + ' Zahlung(en)</strong> beglichen: <span class="badge bg-primary">' + paymentIds + '</span></p>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Settlement Trail Card
                    html += '<div class="col-12">';
                    html += '<div class="card shadow-sm border-0">';
                    html += '<div class="card-header bg-white border-bottom py-3">';
                    html += '<h5 class="mb-0 fw-bold text-primary"><i class="bx bx-list-ul me-2"></i>Alle Zahlungen, die diese Schuld beglichen haben</h5>';
                    html += '</div>';
                    html += '<div class="card-body p-0">';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-hover align-middle mb-0">';
                    html += '<thead class="table-light">';
                    html += '<tr>';
                    html += '<th class="text-center" style="width: 60px;">#</th>';
                    html += '<th style="min-width: 150px;">Datum</th>';
                    html += '<th style="min-width: 120px;">Zahlungs-ID</th>';
                    html += '<th style="min-width: 150px;">Hund</th>';
                    html += '<th style="min-width: 150px;">Kunde</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Rechnungsbetrag</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Betrag erhalten</th>';
                    html += '<th class="text-end" style="min-width: 150px;">Beglichen</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Restbetrag vorher</th>';
                    html += '<th class="text-end" style="min-width: 130px;">Restbetrag nachher</th>';
                    html += '</tr>';
                    html += '</thead>';
                    html += '<tbody>';
                    
                    data.settlement_trail.forEach(function(settlement, index) {
                        var rowClass = index % 2 === 0 ? '' : 'table-light';
                        html += '<tr class="' + rowClass + '">';
                        html += '<td class="text-center"><span class="badge bg-secondary">' + (index + 1) + '</span></td>';
                        html += '<td><i class="bx bx-calendar me-1 text-muted"></i>' + settlement.settling_payment_date + '</td>';
                        html += '<td><span class="badge bg-primary">#' + settlement.settling_payment_id + '</span></td>';
                        html += '<td>' + settlement.dog_name + '</td>';
                        html += '<td>' + settlement.customer_name + '</td>';
                        html += '<td class="text-end"><strong>' + settlement.settling_payment_invoice + '€</strong></td>';
                        html += '<td class="text-end">' + settlement.settling_payment_received + '€</td>';
                        html += '<td class="text-end"><span class="badge bg-success fs-6">' + settlement.amount_settled + '€</span></td>';
                        html += '<td class="text-end"><span class="text-danger fw-bold">' + settlement.balance_before + '€</span></td>';
                        html += '<td class="text-end"><span class="badge ' + (parseFloat(settlement.balance_after) > 0 ? 'bg-danger' : 'bg-success') + ' fs-6">' + settlement.balance_after + '€</span></td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody>';
                    html += '<tfoot class="table-light fw-bold">';
                    html += '<tr>';
                    html += '<td colspan="7" class="text-end align-middle">Gesamt beglichen:</td>';
                    html += '<td class="text-end"><span class="badge bg-success fs-5">' + data.payment.total_settled + '€</span></td>';
                    html += '<td colspan="2"></td>';
                    html += '</tr>';
                    html += '</tfoot>';
                    html += '</table>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                } else {
                    html += '<div class="col-12">';
                    html += '<div class="alert alert-warning border-0 shadow-sm">';
                    html += '<div class="d-flex align-items-start">';
                    html += '<i class="bx bx-info-circle fs-4 me-3 mt-1"></i>';
                    html += '<div>';
                    html += '<h6 class="alert-heading mb-2 fw-bold">Keine Begleichungen gefunden</h6>';
                    html += '<p class="mb-0">Diese Zahlung wurde noch nicht von anderen Zahlungen beglichen.</p>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '</div>'; // Close row
                
                $('#settlementDetailsContent').html(html);
            },
            error: function(xhr) {
                var errorMsg = 'Fehler beim Laden der Begleichungsdetails.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                $('#settlementDetailsContent').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            }
        });
    }
</script>
@endsection
