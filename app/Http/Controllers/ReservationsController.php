<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Dog;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Friend;
use App\Models\ReservationPayment;
use App\Models\Preference;
use App\Helpers\General;
use App\Services\VisitCounterService;
use App\Services\HomepageSyncService;
use Illuminate\Support\Facades\DB;
use App\Models\AdditionalCost;
use App\Models\ReservationPaymentEntry;
use App\Models\ReservationAdditionalCost;
use App\Models\ReservationGroup;
use App\Models\ReservationGroupEntry;
use App\Services\HelloCashService;
use App\Services\InvoiceService;
use App\Services\CustomerBalanceService;
use App\Services\ReservationGroupLifecycleService;
use App\Services\RoomCapacityService;
use App\Helpers\VATCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\HomepageReservationConfirmedMail;
use App\Mail\HomepageReservationCancelledMail;


class ReservationsController extends Controller
{
    protected $homepageSyncService;
    protected $hellocashService;
    protected $invoiceService;

    public function __construct(
        HelloCashService $hellocashService,
        InvoiceService $invoiceService,
        HomepageSyncService $homepageSyncService,
        protected RoomCapacityService $roomCapacityService
    ) {
        $this->hellocashService = $hellocashService;
        $this->invoiceService = $invoiceService;
        $this->homepageSyncService = $homepageSyncService;
    }

    public function reservation(Request $request)
    {
        if (!General::permissions('Reservierung')) {
            return redirect()->route('admin.settings');
        }

        $keyword = $request->input('keyword', '');
        $reservationId = $request->input('id') ?? $request->input('reservation_id');
        $status  = $request->input('status', [3]);
        $order   = $request->input('order', 'desc');
        $perPage = $request->input('per_page', '30');
        $dateRange = $request->input('date_range', '');
        $dateFrom = $request->input('date_from', null);
        $dateTo = $request->input('date_to', null);

        // Parse date_range if provided (format: DD.MM.YYYY - DD.MM.YYYY or DD.MM.YYYY+-+DD.MM.YYYY)
        if (!empty($dateRange) && !$dateFrom && !$dateTo) {
            // Replace URL encoded spaces (+) with regular spaces
            $dateRange = str_replace('+', ' ', $dateRange);
            // Handle both " - " and "-" separators
            $dates = preg_split('/\s*-\s*/', trim($dateRange));
            if (count($dates) === 2) {
                try {
                    $dateFrom = Carbon::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d');
                    $dateTo = Carbon::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d');
                } catch (\Exception $e) {
                    // Invalid date format, ignore
                }
            }
        }

        $query = Reservation::with(['plan', 'dog.customer'])
            ->when($reservationId, function ($q) use ($reservationId) {
                $q->where('id', $reservationId);
            })
            ->when($keyword && !$reservationId, function ($q) use ($keyword) {
                if (is_numeric($keyword)) {
                    $q->where('id', $keyword);
                } else {
                    $q->whereHas('dog', function ($q2) use ($keyword) {
                        $q2->where('name', 'like', "%{$keyword}%")
                            ->orWhereHas('customer', function ($q3) use ($keyword) {
                                $q3->where('name', 'like', "%{$keyword}%")
                                    ->orWhere('phone', 'like', "%{$keyword}%");
                            });
                    });
                }
            })
            ->when($status && !in_array('all', (array)$status), function ($q) use ($status) {
                $q->whereIn('status', (array)$status);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('checkin_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('checkin_date', '<=', $dateTo);
            })
            ->orderBy('checkin_date', $order);

        // Handle pagination
        if ($perPage === 'all') {
            $allReservations = $query->get();
            $total = $allReservations->count();
            // Create a custom paginator for 'all' to maintain compatibility with links()
            $reservations = new \Illuminate\Pagination\LengthAwarePaginator(
                $allReservations,
                $total,
                $total > 0 ? $total : 1,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $perPageInt = (int)$perPage;
            if ($perPageInt <= 0) {
                $perPageInt = 30;
            }
            $reservations = $query->paginate($perPageInt)->appends($request->query());
        }

        return view('admin.reservation.index', compact(
            'reservations',
            'keyword',
            'status',
            'order'
        ));
    }

    public function add_reservation_view()
    {
        if(!General::permissions('Reservierung'))
        {
            return to_route('admin.settings');
        }

        $dogs = Dog::with('customer')->where('died', null)->get();
        $plans = Plan::orderBy('id', 'asc')->get();
        return view ("admin.reservation.add", compact('dogs', 'plans'));
    }

    public function add_reservation(Request $request)
    {
        $request->validate([
            'dog_ids' => 'required',
            'plan_id' => 'required|exists:plans,id',
            'dates' => 'required',
        ]);

        $dogs = $request->dog_ids;
        $planId = (int) $request->plan_id;
        $isMultiDog = is_array($dogs) && count($dogs) > 1;

        // Resolve the customer for all selected dogs (multi-dog = same customer)
        $customerId = null;
        if ($isMultiDog) {
            $firstDog = Dog::find($dogs[0]);
            $customerId = $firstDog?->customer_id;
        }

        // Create a reservation group when multiple dogs are booked together
        $group = null;
        if ($isMultiDog) {
            $group = ReservationGroup::create([
                'customer_id' => $customerId,
                'total_due'   => 0,
                'status'      => 'unpaid',
            ]);
        }

        $createdReservationIds = [];
        foreach($dogs as $id)
        {
            $dog = Dog::find($id);
            foreach($request->dates as $date)
            {
                $ex = explode('-', $date);
                $ex0 = $ex[0].' 00:05';
                $ex1 = $ex[1].' 23:59';
                try {
                    $checkin = Carbon::createFromFormat('d/m/Y H:i', trim($ex0))->toDateTimeString();
                    $checkout = Carbon::createFromFormat('d/m/Y H:i', trim($ex1))->toDateTimeString();
                } catch (\Exception $e) {
                    Log::error('Date parsing error:', ['error' => $e->getMessage(), 'input' => $date]);
                    Session::flash('error', 'Fehler beim Verarbeiten des Datums! Überprüfen Sie Ihre Eingaben.');
                    return back();
                }

                if (Carbon::parse($checkout)->lt(Carbon::parse($checkin))) {
                    Session::flash('error', 'Abholdatum darf nicht vor dem Aufnahmedatum liegen.');
                    return back();
                }

                $checkinDate = Carbon::parse($checkin)->startOfDay();
                $checkoutDate = Carbon::parse($checkout)->startOfDay();
                $daysDiff = $checkinDate->diffInDays($checkoutDate);
                $calculationMode = config('app.days_calculation_mode', 'inclusive');
                $days = $daysDiff === 0 ? 1 : (($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff);

                $res = Reservation::create([
                    'dog_id' => $id,
                    'checkin_date' => $checkin,
                    'checkout_date' => $checkout,
                    'plan_id' => $planId,
                    'status' => 3,
                    'reservation_group_id' => $group?->id,
                ]);
                $createdReservationIds[] = $res->id;

                if ($dog) {
                    if ($days > 1) {
                        $dog->reg_plan = $planId;
                    } else {
                        $dog->day_plan = $planId;
                    }
                    $dog->save();
                }

            }
        }

        $this->createPaymentsForReservationGroups($createdReservationIds);

        // Set the group's total_due from the sum of per-reservation totals
        if ($group) {
            $group->recalculateTotalDue();
        }

        Session::flash('success', 'Reservierung erfolgreich hinzugefügt!');

        if(isset($request->is_dashboard))
        {
            return back();
        }

        return to_route('admin.reservation');
    }

    public function delete_reservation(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $data = Reservation::with(['reservationPayment.entries'])->find($request->id);
        if($data)
        {
            // Prevent deletion if reservation has per-reservation payment entries (group payments are on the group)
            $entryCount = $data->reservationPayment ? $data->reservationPayment->entries->count() : 0;
            if($entryCount > 0)
            {
                Session::flash('error', 'Reservierung kann nicht gelöscht werden, da Zahlungen vorhanden sind.');
                return back();
            }

            $groupId = $data->reservation_group_id;
            
            // Push cancellation to Homepage if remote ID exists before deleting
            if ($data->remote_pfotenstube_homepage_id) {
                $this->homepageSyncService->updateStatus($data, 'storniert');
            }

            DB::transaction(function () use ($data, $groupId) {
                $data->forceDelete();
                if ($groupId) {
                    app(ReservationGroupLifecycleService::class)->afterMemberRemoved((int) $groupId);
                }
            });

            Session::flash('success', 'Reservierung erfolgreich gelöscht!');
        }

        return back();
    }

    public function cancel_reservation(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $data = Reservation::find($request->id);
        if($data)
        {
            // Prevent cancellation of checked-out reservations (status 2) - they have payments
            if($data->status == 2)
            {
                Session::flash('error', 'Ausgecheckte Reservierungen können nicht storniert werden.');
                return back();
            }

            if ((int) $data->status === Reservation::STATUS_ACTIVE) {
                Session::flash('error', 'Eingecheckte Reservierungen können nicht storniert werden. Bitte zuerst auschecken.');
                return back();
            }

            $groupId = $data->reservation_group_id;

            DB::transaction(function () use ($data, $groupId) {
                // Permanently remove payment records - both models use SoftDeletes so
                // forceDelete() is required; a plain delete() only sets deleted_at.
                if ($data->reservationPayment) {
                    $data->reservationPayment->entries()->withTrashed()->forceDelete();
                    $data->reservationPayment->forceDelete();
                }
                $data->additionalCosts()->delete();

                $data->status = Reservation::STATUS_CANCELLED;
                $data->reservation_group_id = null;
                $data->save();

                if ($groupId) {
                    app(ReservationGroupLifecycleService::class)->afterMemberRemoved((int) $groupId);
                }
            });

            // Sync with Homepage if it's a remote reservation
            if ($data->remote_pfotenstube_homepage_id) {
                $this->homepageSyncService->updateStatus($data, 'storniert');
            }
        }

        Session::flash('success', 'Reservierung erfolgreich abgebrochen!');

        return back();
    }

    public function homepage_pending_reservations()
    {
        if (! General::permissions('Reservierung')) {
            return redirect()->route('admin.settings');
        }

        $reservations = Reservation::with(['dog.customer', 'plan'])
            ->where('status', Reservation::STATUS_PENDING_CONFIRMATION)
            ->whereNotNull('remote_pfotenstube_homepage_id')
            ->orderBy('checkin_date')
            ->get();
        $plans = Plan::orderBy('id', 'asc')->get();

        return view('admin.reservation.homepage_pending', compact('reservations', 'plans'));
    }

    public function homepage_pending_confirm(Request $request)
    {
        if (! General::permissions('Reservierung')) {
            return redirect()->route('admin.settings');
        }

        $request->validate([
            'id' => 'required|exists:reservations,id',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $reservation = Reservation::with('dog.customer')->findOrFail($request->id);
        if ((int) $reservation->status !== Reservation::STATUS_PENDING_CONFIRMATION
            || empty($reservation->remote_pfotenstube_homepage_id)) {
            Session::flash('error', 'Diese Anfrage kann nicht bestätigt werden.');

            return back();
        }

        $planId = (int) $request->plan_id;
        $reservation->plan_id = $planId;
        $reservation->status = Reservation::STATUS_RESERVED;
        $reservation->save();

        if ($reservation->dog) {
            $daysDiff = $reservation->checkin_date && $reservation->checkout_date
                ? $reservation->checkin_date->diffInDays($reservation->checkout_date)
                : 0;
            $calculationMode = config('app.days_calculation_mode', 'inclusive');
            $days = $daysDiff === 0 ? 1 : (($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff);

            if ($days > 1) {
                $reservation->dog->reg_plan = $planId;
            } else {
                $reservation->dog->day_plan = $planId;
            }
            $reservation->dog->save();
        }

        $this->createPaymentsForReservationGroups([$reservation->id]);

        $this->homepageSyncService->updateStatus($reservation, 'reserviert');
        $this->tryMailHomepageReservationConfirmed($reservation);

        Session::flash('success', 'Reservierung bestätigt. Der Kunde wurde per E-Mail informiert.');

        return back();
    }

    public function homepage_pending_reject(Request $request)
    {
        if (! General::permissions('Reservierung')) {
            return redirect()->route('admin.settings');
        }

        $request->validate(['id' => 'required|exists:reservations,id']);

        $reservation = Reservation::with('dog.customer')->findOrFail($request->id);
        if ((int) $reservation->status !== Reservation::STATUS_PENDING_CONFIRMATION
            || empty($reservation->remote_pfotenstube_homepage_id)) {
            Session::flash('error', 'Diese Anfrage kann nicht abgelehnt werden.');

            return back();
        }

        $groupId = $reservation->reservation_group_id;

        DB::transaction(function () use ($reservation, $groupId) {
            $reservation->status = Reservation::STATUS_CANCELLED;
            $reservation->reservation_group_id = null;
            $reservation->save();

            if ($groupId) {
                app(ReservationGroupLifecycleService::class)->afterMemberRemoved((int) $groupId);
            }
        });

        $this->homepageSyncService->updateStatus($reservation, 'storniert');
        $this->tryMailHomepageReservationCancelled($reservation);

        Session::flash('success', 'Anfrage abgelehnt. Der Kunde wurde per E-Mail informiert.');

        return back();
    }

    private function tryMailHomepageReservationConfirmed(Reservation $reservation): void
    {
        try {
            $email = $reservation->dog?->customer?->email;
            if ($email) {
                Mail::to($email)->send(new HomepageReservationConfirmedMail($reservation));
            }
        } catch (\Throwable $e) {
            Log::warning('Homepage reservation confirm mail failed: '.$e->getMessage());
        }
    }

    private function tryMailHomepageReservationCancelled(Reservation $reservation): void
    {
        try {
            $email = $reservation->dog?->customer?->email;
            if ($email) {
                Mail::to($email)->send(new HomepageReservationCancelledMail($reservation));
            }
        } catch (\Throwable $e) {
            Log::warning('Homepage reservation cancel mail failed: '.$e->getMessage());
        }
    }

    public function edit_reservation($id)
    {
        if(!General::permissions('Reservierung'))
        {
            return to_route('admin.settings');
        }

        $reservation = Reservation::with('dog')->find($id);
        
        // Prevent editing of checked-out reservations (status 2) - they have payments
        if($reservation && $reservation->status == 2)
        {
            Session::flash('error', 'Ausgecheckte Reservierungen können nicht bearbeitet werden.');
            return redirect()->route('admin.reservation');
        }
        
        $reservation->res_date = Carbon::createFromFormat('Y-m-d H:i:s', $reservation->checkin_date)
            ->format('d/m/Y H:i').' - '.Carbon::createFromFormat('Y-m-d H:i:s', $reservation->checkout_date)
            ->format('d/m/Y H:i');

        return view ("admin.reservation.edit", compact('reservation'));
    }

    public function update_reservation(Request $request)
    {
        $request->validate([
            'dog_id' => 'required',
            'res_id' => 'required',
            'dates' => 'required',
        ]);
        
        // Prevent updating checked-out reservations (status 2) - they have payments
        $reservation = Reservation::find($request->res_id);
        if($reservation && $reservation->status == 2)
        {
            Session::flash('error', 'Ausgecheckte Reservierungen können nicht bearbeitet werden.');
            return redirect()->route('admin.reservation');
        }

        $date = $request->dates;
        $ex = explode('-', $date);
        $ex[0] = trim($ex[0]);
        $ex[1] = trim($ex[1]);
        $ex[0] = str_replace('/','-',$ex[0]).' 00:05';
        $ex[1] = str_replace('/','-',$ex[1]).' 23:59';

        $checkin = Carbon::createFromFormat('d-m-Y H:i',$ex[0])->toDateTimeString();
        $checkout = Carbon::createFromFormat('d-m-Y H:i',$ex[1])->toDateTimeString();

        if (Carbon::parse($checkout)->lt(Carbon::parse($checkin))) {
            Session::flash('error', 'Abholdatum darf nicht vor dem Aufnahmedatum liegen.');
            return redirect()->route('admin.reservation');
        }

        $reservation = Reservation::find($request->res_id);
        $reservation->checkin_date = $checkin;
        $reservation->checkout_date = $checkout;
        $reservation->save();

        Session::flash('success', 'Reservierung erfolgreich aktualisiert!');
        return to_route('admin.reservation');

    }

    public function dogs_in_rooms(Request $request)
    {
        if(!General::permissions('Hund hinzufugen'))
        {
            return to_route('admin.settings');
        }

        if ($request->ajax()) {
            $keyword = isset($request->keyword) ? $request->keyword : "";

            $reservations = Reservation::with(['plan','room', 'dog' => function($query){
                $query->with('customer');
            }]);

            if (!empty($keyword)){
                $reservations = $reservations->whereHas('dog', function($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('customer', function ($query) use ($keyword) {
                            $query->where('name', 'like', '%' . $keyword . '%');
                        });
                });
            }

            $reservations = $reservations->where('room_id', '!=', null)
                ->where('status', Reservation::STATUS_ACTIVE)
                ->whereNotNull('reservation_group_id')
                ->orderBy('checkin_date', 'desc')
                ->get();

            return $reservations;
        }

        $reservations = Reservation::with(['plan', 'room', 'dog' => function ($query) {
            $query->with('customer');
        }])
            ->where('room_id', '!=', null)
            ->where('status', Reservation::STATUS_ACTIVE)
            ->whereNotNull('reservation_group_id')
            ->orderBy('checkin_date', 'desc')
            ->get();

        return view('admin.reservation.dogs-in-rooms', compact('reservations'));
    }

    public function add_reservation_dashboard(Request $request)
    {
        $request->validate([
            'dog_id' => 'required',
            'plan_id' => 'required',
            'dates' => 'required',
            'room_id'=> 'required'
        ]);


        // Check room capacity (Pension + Zuchtverwaltung)
        $rom = Room::find($request->room_id);
        if (! $rom || ! $this->roomCapacityService->canAcceptAdditionalBoarding($rom)) {
            Session::flash('error', 'In diesem Raum ist kein Platz mehr übrig');

            return back();
        }

        // Check if reservation exists or not
        if(isset($request->is_dashboard))
        {
            $isReserved = Reservation::where('dog_id', $request->dog_id)->where('status', 1)->first();
            if($isReserved)
            {
                Session::flash('error', 'Sie können einen Hund nicht zweimal in den Zwinger aufnehmen');
                return back();
            }
        }

        $visitCounter = new VisitCounterService();

        $createdReservationIds = [];
        foreach($request->dates as $date)
        {
            $ex = explode('-', $date);
            $ex0 = $ex[0].' 00:05';
            $ex1 = $ex[1].' 23:59';
            $checkin = Carbon::createFromFormat('d/m/Y H:i', trim($ex0))->toDateTimeString();
            $checkout = Carbon::createFromFormat('d/m/Y H:i', trim($ex1))->toDateTimeString();

            if (Carbon::parse($checkout)->lt(Carbon::parse($checkin))) {
                Session::flash('error', 'Abholdatum darf nicht vor dem Aufnahmedatum liegen.');
                return back();
            }

            $checkinDate = Carbon::parse($checkin)->startOfDay();
            $checkoutDate = Carbon::parse($checkout)->startOfDay();
            $daysDiff = $checkinDate->diffInDays($checkoutDate);
            $calculationMode = config('app.days_calculation_mode', 'inclusive');
            $days = $daysDiff === 0 ? 1 : (($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff);

            $reservation = Reservation::create([
                'dog_id' => $request->dog_id,
                'plan_id' => $request->plan_id,
                'checkin_date' => $checkin,
                'checkout_date' => $checkout,
                'room_id' => $request->room_id,
                'status' => 1,
                'visit_counted' => false,
                'days_counted' => false,
            ]);

            $createdReservationIds[] = $reservation->id;

            $dog = Dog::find($request->dog_id);
            if ($dog) {
                if ($days > 1) {
                    $dog->reg_plan = $request->plan_id;
                } else {
                    $dog->day_plan = $request->plan_id;
                }
                $dog->save();
            }
        }

        $this->createPaymentsForReservationGroups($createdReservationIds);

        Session::flash('success', 'Reservierung erfolgreich hinzugefügt!');

        if(isset($request->is_dashboard))
        {
            return back();
        }

        return to_route('admin.reservation');
    }



    //Move Dog to another room thru dashboard
    public function move_dog(Request $request)
    {
        $request->validate([
            'room_id' => 'required',
            'res_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // Lock room to prevent race conditions
            $room = Room::lockForUpdate()->find($request->room_id);
            if (!$room) {
                throw new \Exception('Raum nicht gefunden');
            }
            
            if (! $this->roomCapacityService->canAcceptAdditionalBoarding($room, (int) $request->res_id)) {
                DB::rollBack();

                return response()->json([
                    'error' => true,
                    'message' => 'Der Raum ist bereits voll',
                ], 422);
            }

            $reservation = Reservation::lockForUpdate()->find($request->res_id);
            if (!$reservation) {
                throw new \Exception('Reservierung nicht gefunden');
            }
            
            $dog_id = $reservation->dog_id;
            $oldStatus = $reservation->status;

            if ((int) $reservation->status === Reservation::STATUS_PENDING_CONFIRMATION) {
                DB::rollBack();

                return response()->json([
                    'error' => true,
                    'message' => 'Diese Online-Anfrage muss zuerst unter „Pfotenstube-Anfragen“ bestätigt werden.',
                ], 422);
            }

            $isCheckin = ($reservation->status != 1) || is_null($reservation->room_id);

            if ($isCheckin) {
                $alreadyCheckedIn = Reservation::where('dog_id', $dog_id)
                    ->where('status', 1)
                    ->where('id', '!=', $reservation->id)
                    ->exists();

                if ($alreadyCheckedIn) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Dieser Hund ist bereits eingecheckt und kann nicht zweimal eingecheckt werden.',
                    ], 422);
                }

                $checkinDate = $reservation->checkin_date instanceof Carbon
                    ? $reservation->checkin_date
                    : ($reservation->checkin_date ? Carbon::parse($reservation->checkin_date) : null);
                $checkoutDate = $reservation->checkout_date instanceof Carbon
                    ? $reservation->checkout_date
                    : ($reservation->checkout_date ? Carbon::parse($reservation->checkout_date) : null);
                $today = Carbon::today();

                // Planned reservation window already fully in the past -> do not auto-fix dates.
                if ($checkoutDate && $checkoutDate->startOfDay()->lt($today)) {
                    DB::rollBack();

                    return response()->json([
                        'error' => true,
                        'message' => 'Diese Reservierung liegt vollständig in der Vergangenheit. Bitte löschen und neu anlegen.',
                    ], 422);
                }

                if (! $checkinDate || $checkinDate->lt($today)) {
                    $reservation->checkin_date = $today;
                }

                if (! $checkoutDate || $checkoutDate->lt($today)) {
                    $reservation->checkout_date = Carbon::now();
                }
            }

            // Check if the NEW dog being added is friends with dogs already in the room
            // Get all dogs currently in the room (excluding the one being added)
            $dogsInRoom = Reservation::where('room_id', $request->room_id)
                ->where('status', 1)
                ->where('dog_id', '!=', $dog_id)
                ->pluck('dog_id');
            
            $showModal = false;
            
            // If there are dogs in the room, check if the new dog is friends with any of them
            if($dogsInRoom->count() > 0)
            {
                foreach($dogsInRoom as $roomDogId)
                {
                    // Check if the new dog and the dog in room are friends
                    $is_friend = Friend::where(function($query) use ($dog_id, $roomDogId) {
                        $query->where([
                            ['dog_id', '=', $dog_id],
                            ['friend_id', '=', $roomDogId],
                        ])->orWhere([
                            ['dog_id', '=', $roomDogId],
                            ['friend_id', '=', $dog_id],
                        ]);
                    })->first();

                    // If at least one dog in the room is not a friend, show the modal
                    if(!$is_friend)
                    {
                        $showModal = true;
                        break; // No need to check further, we'll show the modal
                    }
                }
            }

            $reservation->room_id = $request->room_id;
            $reservation->status = 1;
            $reservation->save();

            $stayTotals = $this->syncReservationPaymentAfterAssignment($reservation);
            if (($stayTotals['previous_total_due'] ?? 0) > ($stayTotals['new_total_due'] ?? 0) + 0.01) {
                try {
                    $this->invoiceService->refreshAdvanceInvoicesAfterStayTotalDropped(
                        $reservation,
                        (float) $stayTotals['previous_total_due']
                    );
                } catch (\Throwable $e) {
                    Log::warning('Advance invoice refresh after room/check-in change failed: '.$e->getMessage(), [
                        'reservation_id' => $reservation->id,
                    ]);
                }
            }

            // Sync with Homepage if it's a remote reservation
            if ($reservation->remote_pfotenstube_homepage_id) {
                // Status 1 means the reservation is now in a room (confirmed)
                $this->homepageSyncService->updateStatus($reservation, 'bestätigt');
            }

            DB::commit();

            return response()->json([
                'showModal' => $showModal,
                'success' => true,
                'message' => 'Raum erfolgreich geändert!',
                'dog_id' => $dog_id,
                'room_id' => $request->room_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Move dog error: ' . $e->getMessage(), [
                'reservation_id' => $request->res_id,
                'room_id' => $request->room_id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => 'Fehler beim Verschieben des Hundes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function move_multiple_dogs(Request $request)
    {
        $request->validate([
            'target_room_id' => 'required',
            'reservation_ids' => 'required|array|min:1',
            'source_room_id' => 'required'
        ]);

        $targetRoom = Room::find($request->target_room_id);
        $sourceRoom = Room::find($request->source_room_id);
        
        if (!$targetRoom || !$sourceRoom) {
            Session::flash('error', 'Ungültige Zimmer ausgewählt!');
            return back();
        }

        $targetCapacity = $this->roomCapacityService->capacityInt($targetRoom);
        $currentOccupancy = $this->roomCapacityService->boardingCount($targetRoom);
        $breeding = $this->roomCapacityService->breedingShelterSlots($targetRoom);
        $dogsToMove = count($request->reservation_ids);

        if (($currentOccupancy + $breeding + $dogsToMove) > $targetCapacity) {
            Session::flash('error', 'Das Zielzimmer hat nicht genug Platz für alle ausgewählten Hunde!');
            return back();
        }

        // Move all selected reservations to the target room
        $movedCount = 0;
        foreach ($request->reservation_ids as $reservationId) {
            $reservation = Reservation::find($reservationId);
            if ($reservation && $reservation->room_id == $request->source_room_id) {
                $reservation->update([
                    'room_id' => $request->target_room_id,
                    'status' => 1
                ]);
                $movedCount++;
            }
        }

        if ($movedCount > 0) {
            Session::flash('success', "{$movedCount} Hund(e) erfolgreich in Zimmer {$targetRoom->number} verschoben!");
        } else {
            Session::flash('error', 'Keine Hunde konnten verschoben werden!');
        }

        return back();
    }

    public function fetch_reservation($id)
    {
        $reservation = Reservation::with(['plan','dog' => function($query){
            $query->with(['customer', 'documents']);
        }])->find($id);

        // // Friends
        $found_friends = [];

        $friends = Friend::where('dog_id', $reservation->dog_id)->orWhere('friend_id', $reservation->dog_id)->get();
        if(count($friends) > 0)
        {
            foreach($friends as $friend)
            {
                $friend_id = ($reservation->dog_id == $friend->dog_id) ? $friend->friend_id : $friend->dog_id;

                if(!in_array($friend_id, $found_friends))
                {
                    $fry = Dog::find($friend_id);
                    $friend->dog = $fry;
                    array_push($found_friends, $friend_id);
                }
            }
        }
        $reservation->dog->friends = $friends;
        
        // Get visit history for this dog
        $visitHistory = $this->getDogVisitHistory($reservation->dog_id);
        $reservation->dog->visit_history = $visitHistory;
        
        if($reservation)
        {
            $reservation->res_date = Carbon::createFromFormat('Y-m-d H:i:s', $reservation->checkin_date)
            ->format('d/m/Y').' - '.Carbon::createFromFormat('Y-m-d H:i:s', $reservation->checkout_date)
            ->format('d/m/Y');

            $dogBirthDate = Carbon::parse($reservation->dog->age);
            $ageInYears = $dogBirthDate->age;
            $ageInMonths = $dogBirthDate->diffInMonths(Carbon::now());
            $remainingMonths = $dogBirthDate->diffInMonths(Carbon::now()) % 12;
            $ageInYearsAndMonths = ($ageInYears == 0) ? "{$remainingMonths} Monate" : "{$ageInYears} Jahre and {$remainingMonths} Monate";
            $reservation->dog->age_in_years = $ageInYearsAndMonths;
        }

        return $reservation;
    }

    private function getDogVisitHistory($dogId)
    {
        // Get all reservations for this dog with their plans and payments
        $reservations = Reservation::with(['plan', 'reservationPayment.entries'])
            ->where('dog_id', $dogId)
            ->where('status', 2) // Only completed reservations
            ->orderBy('checkin_date', 'desc')
            ->get();

        $visitHistory = [];
        $totalDays = 0;
        $totalAmount = 0;

        foreach ($reservations as $reservation) {
            if ($reservation->checkin_date && $reservation->checkout_date) {
                // Normalize to start of day for consistent calculation
                $checkinDate = Carbon::parse($reservation->checkin_date)->startOfDay();
                $checkoutDate = Carbon::parse($reservation->checkout_date)->startOfDay();
                
                // Inclusive: both checkin and checkout dates count (29-30 = 2 days, 29-31 = 3 days)
                // Exclusive: days between count, same-day is 1 (29-30 = 1 day, 29-31 = 2 days)
                $daysDiff = $checkinDate->diffInDays($checkoutDate);
                
                // Same-day checkin/checkout always counts as 1 day
                if ($daysDiff === 0) {
                    $duration = 1;
                } else {
                    $calculationMode = config('app.days_calculation_mode', 'inclusive');
                    $duration = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                }
                
                // Get actual payment data if available
                $dailyPrice = $reservation->plan ? $reservation->plan->price : 0;
                $totalPrice = $duration * $dailyPrice;
                $actualAmount = $totalPrice;
                
                if ($reservation->reservationPayment) {
                    $actualAmount = (float)$reservation->reservationPayment->total_paid;
                }
                
                $visitHistory[] = [
                    'checkin_date' => $checkinDate->format('d.m'),
                    'checkout_date' => $checkoutDate->format('d.m'),
                    'duration' => $duration,
                    'daily_price' => round($dailyPrice, 2),
                    'total_price' => $totalPrice,
                    'actual_amount' => $actualAmount,
                    'year' => $checkinDate->format('Y')
                ];
                
                $totalDays += $duration;
                $totalAmount += $actualAmount;
            }
        }

        return [
            'visits' => $visitHistory,
            'total_days' => $totalDays,
            'total_amount' => $totalAmount
        ];
    }

    public function update_date(Request $request)
    {
        $reservation = Reservation::find($request->id);
        if($reservation)
        {
            $ex = explode('-', $request->date);
            $ex[0] = trim($ex[0]);
            $ex[1] = trim($ex[1]);
            $checkin = Carbon::createFromFormat('d/m/Y',$ex[0])->toDateTimeString();
            $checkout = Carbon::createFromFormat('d/m/Y',$ex[1])->toDateTimeString();
            $reservation->checkin_date = $checkin;
            $reservation->checkout_date = $checkout;
            $reservation->save();
        }
        return true;
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'id'                      => 'required|integer|exists:reservations,id',
            'received_amount'         => 'required|numeric|min:0',
            'total'                   => 'required|numeric|min:0',
            'discount'                => 'required|numeric|min:0|max:100',
            'gateway'                 => 'required|in:Bar,Bank',
            'checkout'                => 'required',
            'additional_costs'        => 'nullable|array',
            'additional_costs.*'      => 'integer|exists:additional_costs,id',
            'additional_cost_values'  => 'nullable|array',
            'additional_cost_values.*' => 'nullable|numeric|min:0',
        ]);

        // Block single checkout for grouped reservations
        $resCheck = Reservation::find($request->id);
        if ($resCheck && $resCheck->reservation_group_id) {
            $msg = 'Diese Reservierung gehört zu einer Gruppenreservierung. Bitte verwenden Sie den Gruppen-Checkout.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            Session::flash('error', $msg);
            return back();
        }

        // Parse checkout date (supports d.m.Y, d/m/Y, or natural)
        $checkoutInput  = trim((string) $request->checkout);
        $checkoutParsed = null;
        foreach (['d.m.Y', 'd/m/Y'] as $fmt) {
            try {
                $checkoutParsed = Carbon::createFromFormat($fmt, $checkoutInput);
                break;
            } catch (\Exception $e) {
            }
        }
        if (! $checkoutParsed) {
            try {
                $checkoutParsed = Carbon::parse($checkoutInput);
            } catch (\Exception $e) {
            }
        }
        if (! $checkoutParsed) {
            Session::flash('error', 'Ungültiges Check-out Datumsformat.');
            return back()->withInput();
        }
        if ($checkoutParsed->isFuture()) {
            Session::flash('error', 'Check-out Datum kann nicht in der Zukunft liegen.');
            return back()->withInput();
        }

        $sendToHelloCash = $request->boolean('send_to_hellocash');
        $amountTolerance = 0.01;

        DB::beginTransaction();
        try {
            $reservation = Reservation::with(['dog.customer', 'plan', 'reservationPayment.entries.invoice'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($reservation->status === Reservation::STATUS_CHECKED_OUT) {
                throw new \Exception('Diese Reservierung wurde bereits ausgecheckt');
            }

            $checkinDate  = $reservation->checkin_date
                ? Carbon::parse($reservation->checkin_date)->startOfDay()
                : null;
            $checkoutDate = $checkoutParsed->copy()->startOfDay();

            if ($checkinDate && $checkoutDate->lt($checkinDate)) {
                throw new \Exception('Check-out-Datum darf nicht vor dem Check-in-Datum liegen');
            }

            // ── Determine actual days ───────────────────────────────────────
            $daysDiff = $checkinDate ? $checkinDate->diffInDays($checkoutDate) : 0;
            $calculationMode = config('app.days_calculation_mode', 'inclusive');
            $days = ($daysDiff === 0) ? 1 : (($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff);

            // ── Detect early / late vs. planned checkout ────────────────────
            $plannedCheckoutDate = $reservation->checkout_date
                ? Carbon::parse($reservation->checkout_date)->startOfDay()
                : null;

            $deviationDays = $plannedCheckoutDate ? $plannedCheckoutDate->diffInDays($checkoutDate, false) : 0;
            $isEarlyCheckout = $deviationDays < 0;   // actual < planned
            $isLateCheckout  = $deviationDays > 0;   // actual > planned

            // ── Plan & costs ────────────────────────────────────────────────
            $planId = (int)($request->price_plan ?? $reservation->plan_id);
            $plan   = Plan::find($planId);
            if (! $plan) {
                throw new \Exception('Ausgewählter Preisplan existiert nicht');
            }

            $isFlatRate = (int) $plan->flat_rate === 1;
            $planCost   = (float) $plan->price;
            if (! $isFlatRate) {
                $planCost *= $days;
            }

            // Rebuild additional costs for actual stay
            ReservationAdditionalCost::where('reservation_id', $reservation->id)->delete();
            $additionalTotal = 0.0;
            $selectedCosts   = $request->additional_costs ?? [];
            if (is_array($selectedCosts) && count($selectedCosts) > 0) {
                foreach ($selectedCosts as $costId) {
                    $priceInput = $request->additional_cost_values[$costId] ?? null;
                    $costModel  = AdditionalCost::find($costId);
                    $price      = $priceInput !== null ? (float) $priceInput : (float)($costModel?->price ?? 0.0);
                    ReservationAdditionalCost::create([
                        'reservation_id'    => $reservation->id,
                        'additional_cost_id' => $costModel?->id,
                        'title'             => $costModel?->title ?? 'Zusatzkosten',
                        'price'             => $price,
                        'quantity'          => 1,
                    ]);
                    $additionalTotal += $price;
                }
            }

            $discountPercentage = $isFlatRate ? 0 : (int) $request->discount;
            $vatPercentage      = Preference::get('vat_percentage', 20);
            $vatMode            = config('app.vat_calculation_mode', 'exclusive');

            // Net → gross calculation
            $baseAmount = $planCost + $additionalTotal;
            if ($vatMode === 'inclusive') {
                $netTotal = VATCalculator::getNetFromGross($baseAmount, $vatPercentage);
            } else {
                $netTotal = $baseAmount;
            }
            if ($discountPercentage > 0) {
                $netTotal *= (1 - ($discountPercentage / 100));
            }
            $netTotal   = round($netTotal, 2);
            $vatAmount  = VATCalculator::calculateVATAmount($netTotal, $vatPercentage);
            $grossTotal = round($netTotal + $vatAmount, 2);

            // Inclusive list prices are already gross; net+VAT reconstruction can drift by cents from
            // rounding VAT on extracted net. Match sticker total when no percentage discount (same as Reservation::getGrossTotalAttribute).
            if ($vatMode === 'inclusive' && $discountPercentage === 0) {
                $grossTotal = round($baseAmount, 2);
                $netTotal   = VATCalculator::getNetFromGross($grossTotal, $vatPercentage);
                $vatAmount  = round($grossTotal - $netTotal, 2);
            }

            // ── Update reservation to actual checkout state ──────────────────
            $reservation->update([
                'checkout_date' => $checkoutParsed->format('Y-m-d H:i:s'),
                'status'        => Reservation::STATUS_CHECKED_OUT,
                'plan_id'       => $planId,
            ]);

            if ($reservation->remote_pfotenstube_homepage_id) {
                $this->homepageSyncService->updateStatus($reservation, 'abgeschlossen');
            }

            if ($reservation->dog) {
                if ($days > 1) {
                    $reservation->dog->reg_plan = $planId;
                } else {
                    $reservation->dog->day_plan = $planId;
                }
                $reservation->dog->save();
            }

            // ── Payment header ───────────────────────────────────────────────
            $paymentHeader = $reservation->reservationPayment;
            if (! $paymentHeader) {
                $paymentHeader = ReservationPayment::create([
                    'res_id'    => $reservation->id,
                    'total_due' => $grossTotal,
                    'status'    => 'unpaid',
                ]);
            } else {
                $paymentHeader->update(['total_due' => $grossTotal]);
            }

            // ── EARLY CHECKOUT: cancel old invoices + entries, create corrective ─
            $entriesCreated = [];
            $advancePaidBeforeCheckout = 0.0;
            $overpaidAmount = null;
            $replacedEarlyOverpaidEntries = false;

            if ($isEarlyCheckout) {
                $previousTotalPaid  = $paymentHeader->entries()
                    ->where('status', 'active')->sum('amount');
                $advancePaidBeforeCheckout = (float) $previousTotalPaid;
                $receivedNow = (float)($request->received_amount ?? 0);

                // Refund case: customer already overpaid compared to corrected early-checkout total.
                // Replace historic active lines with one corrected settlement line.
                if ($previousTotalPaid > $grossTotal + $amountTolerance) {
                    $lastPaymentMethod  = 'Bar';
                    $lastActiveEntry    = $paymentHeader->entries()
                        ->where('status', 'active')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($lastActiveEntry && ! empty($lastActiveEntry->method)) {
                        $lastPaymentMethod = $lastActiveEntry->method;
                    }

                    foreach ($paymentHeader->entries as $entry) {
                        if ($entry->status === 'active') {
                            if ($entry->invoice) {
                                $entry->invoice->update(['status' => 'cancelled']);
                            }
                            $entry->update(['status' => 'cancelled']);
                        }
                    }

                    $newActiveAmount = round($grossTotal, 2);
                    $overpaidAmount  = round($previousTotalPaid - $grossTotal, 2);

                    if ($newActiveAmount > 0) {
                        $correctiveEntry = ReservationPaymentEntry::create([
                            'res_payment_id'   => $paymentHeader->id,
                            'amount'           => $newActiveAmount,
                            'overpaid_amount'  => $overpaidAmount,
                            'method'           => $lastPaymentMethod,
                            'type'             => 'final',
                            'transaction_date' => Carbon::now(),
                            'note'             => 'Korrekturbuchung: vorzeitiger Check-out am '
                                . $checkoutParsed->format('d.m.Y')
                                . ' (' . abs($deviationDays) . ' Tag(e) früher)',
                            'status'           => 'active',
                        ]);
                        $entriesCreated[] = $correctiveEntry;
                    }

                    $replacedEarlyOverpaidEntries = true;
                    $advancePaidBeforeCheckout = 0.0;
                } else {
                    // Non-refund early checkout: keep prior payments/invoices, only settle remaining if needed.
                    $remainingAfterAdvance = round($grossTotal - $previousTotalPaid, 2);

                    if ($receivedNow > 0 && $remainingAfterAdvance > $amountTolerance) {
                        $settlementAmount = min($receivedNow, $remainingAfterAdvance);
                        if ($settlementAmount > 0) {
                            $entriesCreated[] = ReservationPaymentEntry::create([
                                'res_payment_id'   => $paymentHeader->id,
                                'amount'           => $settlementAmount,
                                'method'           => $request->input('gateway', 'Bar'),
                                'type'             => 'final',
                                'transaction_date' => Carbon::now(),
                                'status'           => 'active',
                            ]);
                        }
                    }
                }
            } else {
                // ── LATE / ON-TIME CHECKOUT ──────────────────────────────────
                $previousPaid = $paymentHeader->entries()
                    ->where('status', 'active')->sum('amount');
                $advancePaidBeforeCheckout = (float) $previousPaid;
                $remaining    = round($grossTotal - $previousPaid, 2);

                $gateway     = $request->input('gateway', 'Bar');
                $amountPaid  = (float)($request->received_amount ?? 0);

                if ($amountPaid > 0) {
                    // Nothing left to settle (already covered by advance): treat extra payment as advance credit
                    if ($remaining < $amountTolerance) {
                        $entriesCreated[] = ReservationPaymentEntry::create([
                            'res_payment_id'   => $paymentHeader->id,
                            'amount'           => $amountPaid,
                            'method'           => $gateway,
                            'type'             => 'advance',
                            'transaction_date' => Carbon::now(),
                            'status'           => 'active',
                        ]);
                    } elseif (
                        $amountPaid <= $remaining + $amountTolerance
                        || abs($amountPaid - $remaining) <= $amountTolerance
                    ) {
                        // Single settlement line: paying the open balance at checkout (final), not a split advance/final pair
                        $entriesCreated[] = ReservationPaymentEntry::create([
                            'res_payment_id'   => $paymentHeader->id,
                            'amount'           => $amountPaid,
                            'method'           => $gateway,
                            'type'             => 'final',
                            'transaction_date' => Carbon::now(),
                            'status'           => 'active',
                        ]);
                    } else {
                        // Genuine overpayment: remainder to final, excess as advance
                        if ($remaining > $amountTolerance) {
                            $entriesCreated[] = ReservationPaymentEntry::create([
                                'res_payment_id'   => $paymentHeader->id,
                                'amount'           => $remaining,
                                'method'           => $gateway,
                                'type'             => 'final',
                                'transaction_date' => Carbon::now(),
                                'status'           => 'active',
                            ]);
                        }
                        $entriesCreated[] = ReservationPaymentEntry::create([
                            'res_payment_id'   => $paymentHeader->id,
                            'amount'           => round($amountPaid - max($remaining, 0), 2),
                            'method'           => $gateway,
                            'type'             => 'advance',
                            'transaction_date' => Carbon::now(),
                            'status'           => 'active',
                        ]);
                    }
                }
            }

            // ── Sync payment header status ───────────────────────────────────
            $totalPaid = $paymentHeader->entries()
                ->where('status', 'active')->sum('amount');
            if ($totalPaid >= $grossTotal - $amountTolerance) {
                $paymentHeader->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $paymentHeader->update(['status' => 'partial']);
            } else {
                $paymentHeader->update(['status' => 'unpaid']);
            }

            DB::commit();

            // ── Post-commit: checkout invoice + final invoice + HelloCash ────
            $reservation->load('additionalCosts', 'reservationPayment.entries');

            // Build gross line items for the checkout breakdown invoice
            $vatModeForBreakdown = config('app.vat_calculation_mode', 'exclusive');
            $planUnitPriceRaw    = (float) $plan->price;
            $planUnitGross = $vatModeForBreakdown === 'inclusive'
                ? $planUnitPriceRaw
                : round($planUnitPriceRaw * (1 + $vatPercentage / 100), 2);
            $planGrossBeforeDiscount = $isFlatRate ? $planUnitGross : round($planUnitGross * $days, 2);

            $additionalCostsBreakdown = $reservation->additionalCosts->map(function ($ac) use ($vatModeForBreakdown, $vatPercentage) {
                $qty      = (int) ($ac->quantity ?? 1);
                $rawPrice = (float) $ac->price;
                $unitGross = $vatModeForBreakdown === 'inclusive'
                    ? $rawPrice
                    : round($rawPrice * (1 + $vatPercentage / 100), 2);
                return [
                    'title'    => $ac->title,
                    'quantity' => $qty,
                    'gross'    => round($unitGross * $qty, 2),
                ];
            })->toArray();

            $checkoutEntryMethods = collect($entriesCreated)->pluck('method')->unique()->values()->toArray();
            $lastEntryId          = collect($entriesCreated)->last()?->id;

            $checkoutBreakdown = [
                'plan_title'               => $plan->title,
                'days'                     => $days,
                'is_flat_rate'             => $isFlatRate,
                'plan_unit_gross'          => $planUnitGross,
                'plan_gross_before_discount' => $planGrossBeforeDiscount,
                'additional_costs'         => $additionalCostsBreakdown,
                'discount_percentage'      => $discountPercentage,
                'gross_total'              => $grossTotal,
                'advance_paid'             => $advancePaidBeforeCheckout,
                'overpaid_amount'          => $overpaidAmount ?? 0.0,
                'is_early_checkout'        => $isEarlyCheckout,
                'vat_percentage'           => $vatPercentage,
                'checkout_entry_methods'   => $checkoutEntryMethods,
                'last_entry_id'            => $lastEntryId,
                'checkin_date'             => $checkinDate,
                'actual_checkout_date'     => $checkoutParsed,
            ];

            // Only generate Schlussrechnung + Interne when there is a remaining balance at checkout.
            // If the full amount was already covered by the advance payment (balance = 0), the
            // Anzahlung receipt is the only invoice needed - no further documents are created.
            $checkoutInvoiceResult = null;
            $finalInvoiceResult    = null;

            $balanceDueAtCheckout = max(0.0, round($grossTotal - $advancePaidBeforeCheckout, 2));
            $hasAdvanceBeforeCheckout = $advancePaidBeforeCheckout > $amountTolerance;

            if ($balanceDueAtCheckout > $amountTolerance) {
                // 1. Customer invoice (Schlussrechnung) - due amount at checkout.
                $checkoutInvoiceResult = $this->invoiceService->generateCheckoutInvoice($reservation, $checkoutBreakdown);

                // 2. Internal final invoice is only needed when an advance already exists.
                // If no advance existed, internal and customer invoices would be duplicates.
                if ($hasAdvanceBeforeCheckout) {
                    $allActiveEntries = $reservation->reservationPayment?->entries()->where('status', 'active')->get() ?? collect();
                    if ($allActiveEntries->isNotEmpty()) {
                        $finalInvoiceResult = $this->invoiceService->generateInvoice($reservation, $allActiveEntries, 'final');
                    }
                }
            }

            // Early refund correction: always generate a corrected local final invoice
            // after replacing overpaid historical entries.
            if ($isEarlyCheckout && $replacedEarlyOverpaidEntries) {
                $allActiveEntries = $reservation->reservationPayment?->entries()->where('status', 'active')->get() ?? collect();
                if ($allActiveEntries->isNotEmpty()) {
                    $finalInvoiceResult = $this->invoiceService->generateInvoice($reservation, $allActiveEntries, 'final');
                }
            }

            $customerId = $reservation->dog?->customer_id;
            if ($customerId) {
                (new CustomerBalanceService())->getBalance($customerId);
            }

            $helloCashResult = null;
            if ($sendToHelloCash && ! $isEarlyCheckout) {
                $cashEntries = $paymentHeader->entries()
                    ->where('status', 'active')
                    ->where('method', 'Bar')
                    ->get();
                $cashTotal = $cashEntries->sum('amount');
                if ($cashTotal > $amountTolerance) {
                    $items = [];
                    foreach ($cashEntries as $entry) {
                        $label   = $entry->type === 'advance' ? 'Anzahlung' : 'Zahlung';
                        $items[] = [
                            'item_name'     => $label,
                            'item_quantity' => 1.0,
                            'item_price'    => round((float) $entry->amount, 2),
                            'item_taxRate'  => (float) $vatPercentage,
                        ];
                    }
                    $helloCashResult = $this->hellocashService->createCashPaymentInvoice([
                        'reservation_id'        => $reservation->id,
                        'customer_id'           => $reservation->dog?->customer_id,
                        'hellocash_customer_id' => $reservation->dog?->customer?->hellocash_customer_id,
                        'items'                 => $items,
                        'payment_method'        => 'Bar',
                    ]);
                }
            }

            // Build a human-readable summary for the flash message
            if ($isEarlyCheckout) {
                $overpaidMsg = isset($overpaidAmount) && $overpaidAmount > 0
                    ? ' Rückgabe: ' . number_format($overpaidAmount, 2) . '€.'
                    : '';
                Session::flash('success', 'Frühzeitiger Check-out erfolgreich. Neue Gesamtsumme: '
                    . number_format($grossTotal, 2) . '€.' . $overpaidMsg);
            } elseif ($isLateCheckout) {
                Session::flash('success', 'Später Check-out erfolgreich. Aktualisierte Gesamtsumme: '
                    . number_format($grossTotal, 2) . '€.');
            } else {
                Session::flash('success', 'Kaufabwicklung erfolgreich.');
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'          => true,
                    'message'          => Session::get('success'),
                    'checkout_type'    => $isEarlyCheckout ? 'early' : ($isLateCheckout ? 'late' : 'ontime'),
                    'gross_total'      => $grossTotal,
                    'overpaid_amount'  => $isEarlyCheckout ? ($overpaidAmount ?? 0) : 0,
                    'checkout_invoice' => $checkoutInvoiceResult,
                    'final_invoice'    => $finalInvoiceResult,
                    'hellocash'        => $helloCashResult,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout error: ' . $e->getMessage(), [
                'reservation_id' => $request->id,
                'trace'          => $e->getTraceAsString(),
            ]);
            Session::flash('error', 'Fehler bei der Kaufabwicklung: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fehler bei der Kaufabwicklung: ' . $e->getMessage(),
                ], 500);
            }
        }

        return back();
    }

    public function update_reservation_room(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'room_id' => 'required',
        ]);

        $id = $request->id;
        $room_id = $request->room_id;

        $res = Reservation::findOrFail($id);
        $dog_id = $res->dog_id;
        $oldStatus = $res->status;

        if ((int) $res->status === Reservation::STATUS_PENDING_CONFIRMATION) {
            return response()->json([
                'error' => true,
                'message' => 'Diese Online-Anfrage muss zuerst unter „Pfotenstube-Anfragen“ bestätigt werden.',
            ], 422);
        }

        if ($request->drag_type === 'checkin') {
            $alreadyCheckedIn = Reservation::where('dog_id', $dog_id)
                ->where('status', 1)
                ->where('id', '!=', $res->id)
                ->exists();

            if ($alreadyCheckedIn) {
                return response()->json([
                    'error' => true,
                    'message' => 'Dieser Hund ist bereits in einem Zimmer eingecheckt und kann nicht zweimal eingecheckt werden.',
                ], 422);
            }
        }

        $room = Room::findOrFail($room_id);
        if (! $this->roomCapacityService->canAcceptAdditionalBoarding($room, (int) $res->id)) {
            return response()->json([
                'error' => true,
                'message' => 'Der Raum ist bereits voll',
            ], 422);
        }

        // Check if the NEW dog being added is friends with dogs already in the room
        // Get all dogs currently in the room (excluding the one being added)
        $dogsInRoom = Reservation::where('room_id', $room_id)
            ->where('status', 1)
            ->where('dog_id', '!=', $dog_id)
            ->pluck('dog_id');
        
        $showModal = false;
        
        // If there are dogs in the room, check if the new dog is friends with any of them
        if($dogsInRoom->count() > 0)
        {
            foreach($dogsInRoom as $roomDogId)
            {
                // Check if the new dog and the dog in room are friends
                $is_friend = Friend::where(function($query) use ($dog_id, $roomDogId) {
                    $query->where([
                        ['dog_id', '=', $dog_id],
                        ['friend_id', '=', $roomDogId],
                    ])->orWhere([
                        ['dog_id', '=', $roomDogId],
                        ['friend_id', '=', $dog_id],
                    ]);
                })->first();

                // If at least one dog in the room is not a friend, show the modal
                if(!$is_friend)
                {
                    $showModal = true;
                    break; // No need to check further, we'll show the modal
                }
            }
        }

        $res->room_id = $room_id;
        
        if(isset($request->status))
        {
            $res->status = $request->status;
        }
        
        // Update checkin_date and checkout_date when dragging from reservation area to room (check-in)
        if($request->drag_type === 'checkin')
        {
            $checkinDate = $res->checkin_date instanceof Carbon ? $res->checkin_date : ($res->checkin_date ? Carbon::parse($res->checkin_date) : null);
            $checkoutDate = $res->checkout_date instanceof Carbon ? $res->checkout_date : ($res->checkout_date ? Carbon::parse($res->checkout_date) : null);
            $today = Carbon::today();

            // Planned reservation window already fully in the past -> do not auto-fix dates.
            if ($checkoutDate && $checkoutDate->startOfDay()->lt($today)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Diese Reservierung liegt vollständig in der Vergangenheit. Bitte löschen und neu anlegen.',
                ], 422);
            }

            if (! $checkinDate || $checkinDate->lt($today)) {
                $res->checkin_date = $today;
            }

            if (! $checkoutDate || $checkoutDate->lt($today)) {
                $res->checkout_date = Carbon::now();
            }
        }
        
        $res->save();

        $stayTotals = $this->syncReservationPaymentAfterAssignment($res);
        if (($stayTotals['previous_total_due'] ?? 0) > ($stayTotals['new_total_due'] ?? 0) + 0.01) {
            try {
                $this->invoiceService->refreshAdvanceInvoicesAfterStayTotalDropped(
                    $res,
                    (float) $stayTotals['previous_total_due']
                );
            } catch (\Throwable $e) {
                Log::warning('Advance invoice refresh after room update failed: '.$e->getMessage(), [
                    'reservation_id' => $res->id,
                ]);
            }
        }

        // Sync with Homepage if it's a remote reservation
        if ($res->remote_pfotenstube_homepage_id && $res->status == 1) {
            // Status 1 means the reservation is now in a room (confirmed)
            $this->homepageSyncService->updateStatus($res, 'bestätigt');
        }

        return response()->json([
            'showModal' => $showModal,
            'success' => true,
        ]);
    }

    public function dogs_in_rooms_checkout()
    {
        if(!General::permissions('Hund hinzufugen'))
        {
            return to_route('admin.settings');
        }

        Session::flash('success', 'Bitte wählen Sie im Mehrfachkasse-Bereich die Hunde eines Kunden aus und öffnen Sie die Kasse über „Kasse“.');

        return redirect()->route('admin.dogs.in.rooms');
    }

    public function dogs_in_rooms_checkout_post(Request $request)
    {
        $request->validate([
            'entry' => 'required|array|min:1',
            'entry.*' => 'integer|exists:reservations,id',
        ]);

        $entries = $request->entry;

        $reservations = Reservation::with(['plan', 'room', 'reservationPayment.entries', 'additionalCosts', 'reservationGroup.entries', 'dog' => function ($query) {
            $query->with('customer', 'day_plan_obj');
        }])->whereIn('id', $entries)->get();

        foreach ($reservations as $reservation) {
            if (! $reservation->reservation_group_id) {
                Session::flash('error', 'Mehrfachkasse ist nur für zusammen eingecheckte Gruppen möglich.');

                return redirect()->route('admin.dogs.in.rooms');
            }
            if ($reservation->room_id === null || (int) $reservation->status !== Reservation::STATUS_ACTIVE) {
                Session::flash('error', 'Eine oder mehrere ausgewählte Reservierungen sind nicht mehr aktiv im Zimmer.');

                return redirect()->route('admin.dogs.in.rooms');
            }
        }

        $customerIds = $reservations->map(fn ($r) => $r->dog?->customer_id)->filter()->unique()->values();
        if ($customerIds->count() !== 1) {
            Session::flash('error', 'Bitte wählen Sie nur Hunde eines Kunden für die Mehrfachkasse aus.');

            return redirect()->route('admin.dogs.in.rooms');
        }

        $groupIds = $reservations->pluck('reservation_group_id')->unique()->filter();
        foreach ($groupIds as $gid) {
            $expectedIds = Reservation::query()
                ->where('reservation_group_id', $gid)
                ->where('room_id', '!=', null)
                ->where('status', Reservation::STATUS_ACTIVE)
                ->pluck('id')
                ->sort()
                ->values();
            $selectedIds = $reservations->where('reservation_group_id', $gid)->pluck('id')->sort()->values();
            if ($expectedIds->count() !== $selectedIds->count() || $expectedIds->diff($selectedIds)->isNotEmpty()) {
                Session::flash('error', 'Für die Mehrfachkasse müssen alle noch im Zimmer befindlichen Hunde der betroffenen Gruppe(n) ausgewählt sein.');

                return redirect()->route('admin.dogs.in.rooms');
            }
        }

        $reservationGroups = [];
        foreach ($reservations as $reservation) {
            $gid = $reservation->reservation_group_id;
            if (! isset($reservationGroups[$gid])) {
                $group = $reservation->reservationGroup;
                $reservationGroups[$gid] = [
                    'group'        => $group,
                    'customer'     => $group?->customer ?? $reservation->dog?->customer,
                    'reservations' => [],
                ];
            }
            $reservationGroups[$gid]['reservations'][] = $reservation;
        }

        $groupedReservations = [];
        $checkoutCustomer = $reservations->first()->dog?->customer;
        $bulkCheckoutOnly = true;

        $plans = Plan::orderBy('id', 'asc')->get();
        $vatPercentage = Preference::get('vat_percentage', 20);
        $additionalCosts = AdditionalCost::orderBy('title', 'asc')->get();

        return view('admin.reservation.checkout', compact(
            'groupedReservations',
            'reservationGroups',
            'plans',
            'vatPercentage',
            'additionalCosts',
            'checkoutCustomer',
            'bulkCheckoutOnly'
        ));
    }

    public function dogs_in_rooms_update_checkout(Request $request)
    {
        if(count($request->res_id) == 0)
        {
            return back();
        }

        $request->validate([
            'res_id' => 'required|array|min:1',
            'res_id.*' => 'required|integer|exists:reservations,id',
            'plan_id' => 'required|array',
            'plan_id.*' => 'required|integer|exists:plans,id',
            'discount' => 'required|array',
            'discount.*' => 'required|numeric|min:0|max:100',
            'payment_mode' => 'required|array',
            'payment_mode.*' => 'required|string|in:single,split',
        ]);

        $amountTolerance = 0.01;
        $visitCounter = new VisitCounterService();
        $failures = [];
        $helloCashCustomers = $request->send_to_hellocash ?? [];

        foreach($request->res_id as $key => $res_id)
        {
            DB::beginTransaction();
            try {
                $reservation = Reservation::with(['dog.customer', 'plan', 'reservationPayment.entries'])->lockForUpdate()->findOrFail($res_id);
                if ($reservation->status === Reservation::STATUS_CHECKED_OUT) {
                    throw new \Exception('Diese Reservierung wurde bereits ausgecheckt');
                }
                if (!$reservation->dog) {
                    throw new \Exception('Hund für diese Reservierung nicht gefunden');
                }
                if ($reservation->reservation_group_id) {
                    throw new \Exception('Reservierung #' . $reservation->id . ' gehört zu einer Gruppe - bitte Gruppen-Checkout verwenden.');
                }

                $plannedCheckout = $reservation->checkout_date ? Carbon::parse($reservation->checkout_date) : null;
                $checkoutDate = isset($request->checkout_date[$key]) && !empty($request->checkout_date[$key])
                    ? Carbon::parse($request->checkout_date[$key])
                    : Carbon::now();

                if ($checkoutDate->isFuture()) {
                    throw new \Exception('Check-out-Datum darf nicht in der Zukunft liegen');
                }

                if ($reservation->checkin_date) {
                    $checkinDate = Carbon::parse($reservation->checkin_date)->startOfDay();
                    $checkoutDay = $checkoutDate->copy()->startOfDay();
                    if ($checkoutDay->lt($checkinDate)) {
                        throw new \Exception('Check-out-Datum darf nicht vor dem Check-in-Datum liegen');
                    }
                }

                if (!$reservation->visit_counted) {
                    $visitCounter->incrementVisit($reservation->dog_id);
                    $reservation->visit_counted = true;
                }
                if (!$reservation->days_counted && $reservation->checkin_date) {
                    $visitCounter->incrementDays($reservation->dog_id, $reservation->checkin_date, $checkoutDate);
                    $reservation->days_counted = true;
                }

                $planId = (int)($request->plan_id[$key] ?? $reservation->plan_id);
                $plan = Plan::find($planId);
                if (!$plan) {
                    throw new \Exception('Ausgewählter Preisplan existiert nicht');
                }

                $reservation->update([
                    'checkout_date' => $checkoutDate->format('Y-m-d H:i:s'),
                    'status' => Reservation::STATUS_CHECKED_OUT,
                    'plan_id' => $planId,
                ]);

                if ($reservation->remote_pfotenstube_homepage_id) {
                    $this->homepageSyncService->updateStatus($reservation, 'abgeschlossen');
                }

                // Calculate days
                $checkinDate = Carbon::parse($reservation->checkin_date)->startOfDay();
                $checkoutDay = $checkoutDate->copy()->startOfDay();
                $daysDiff = $checkinDate->diffInDays($checkoutDay);
                if ($daysDiff === 0) {
                    $days = 1;
                } else {
                    $calculationMode = config('app.days_calculation_mode', 'inclusive');
                    $days = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                }

                $isFlatRate = (int)$plan->flat_rate === 1;
                $planCost = (float)$plan->price;
                if (!$isFlatRate) {
                    $planCost *= $days;
                }

                $discountPercentage = $isFlatRate ? 0 : (int)($request->discount[$key] ?? 0);

                // Store additional costs
                ReservationAdditionalCost::where('reservation_id', $reservation->id)->delete();
                $additionalTotal = 0.0;
                $selectedCosts = $request->additional_costs[$key] ?? [];
                foreach ($selectedCosts as $costId) {
                    $priceInput = $request->additional_cost_values[$key][$costId] ?? null;
                    $price = $priceInput !== null ? (float)$priceInput : 0.0;
                    $costModel = AdditionalCost::find($costId);
                    $title = $costModel?->title ?? 'Zusatzkosten';

                    ReservationAdditionalCost::create([
                        'reservation_id' => $reservation->id,
                        'additional_cost_id' => $costModel?->id,
                        'title' => $title,
                        'price' => $price,
                        'quantity' => 1,
                    ]);

                    $additionalTotal += $price;
                }

                $vatPercentage = Preference::get('vat_percentage', 20);
                $vatMode = config('app.vat_calculation_mode', 'exclusive');

                $netTotal = 0.0;
                if ($vatMode === 'inclusive') {
                    $netTotal = VATCalculator::getNetFromGross($planCost + $additionalTotal, $vatPercentage);
                } else {
                    $netTotal = $planCost + $additionalTotal;
                }

                if ($discountPercentage > 0) {
                    $netTotal = $netTotal * (1 - ($discountPercentage / 100));
                }

                $netTotal = round($netTotal, 2);
                $vatAmount = VATCalculator::calculateVATAmount($netTotal, $vatPercentage);
                $grossTotal = round($netTotal + $vatAmount, 2);

                $baseAmount = $planCost + $additionalTotal;
                if ($vatMode === 'inclusive' && $discountPercentage === 0) {
                    $grossTotal = round($baseAmount, 2);
                    $netTotal = VATCalculator::getNetFromGross($grossTotal, $vatPercentage);
                    $vatAmount = round($grossTotal - $netTotal, 2);
                }

                $paymentHeader = $reservation->reservationPayment;
                if (!$paymentHeader) {
                    $paymentHeader = ReservationPayment::create([
                        'res_id' => $reservation->id,
                        'total_due' => $grossTotal,
                        'status' => 'unpaid',
                    ]);
                } else {
                    $paymentHeader->update(['total_due' => $grossTotal]);
                }

                // Early checkout correction
                $previousTotalPaid = $paymentHeader->entries()->where('status', 'active')->sum('amount');
                $activePaid = $previousTotalPaid;
                if ($plannedCheckout && $checkoutDate->lt($plannedCheckout)) {
                    $lastMethod = 'Bar';
                    $lastEntry = $paymentHeader->entries()->where('status', 'active')->orderByDesc('transaction_date')->first();
                    if ($lastEntry && !empty($lastEntry->method)) {
                        $lastMethod = $lastEntry->method;
                    }

                    foreach ($paymentHeader->entries as $entry) {
                        if ($entry->status === 'active') {
                            if ($entry->invoice) {
                                $entry->invoice->update(['status' => 'cancelled']);
                            }
                            $entry->update(['status' => 'cancelled']);
                        }
                    }

                    $correctiveAmount = min($previousTotalPaid, $grossTotal);
                    $overpaidAmount = $previousTotalPaid > $grossTotal ? ($previousTotalPaid - $grossTotal) : null;

                    $correctiveEntry = ReservationPaymentEntry::create([
                        'res_payment_id' => $paymentHeader->id,
                        'amount' => $correctiveAmount,
                        'overpaid_amount' => $overpaidAmount,
                        'method' => $lastMethod,
                        'type' => 'final',
                        'transaction_date' => Carbon::now(),
                        'note' => 'Korrekturbuchung wegen vorzeitiger Abreise',
                        'status' => 'active',
                    ]);

                    $this->invoiceService->generateInvoice($reservation, $correctiveEntry);
                    $activePaid = $correctiveAmount;
                }

                $remaining = round($grossTotal - $activePaid, 2);

                $createdEntries = [];
                if ($remaining > $amountTolerance) {
                    $mode = $request->payment_mode[$key] ?? 'single';
                    if ($mode === 'split') {
                        $cashAmount = (float)($request->payment_amount_cash[$key] ?? 0);
                        $bankAmount = (float)($request->payment_amount_bank[$key] ?? 0);
                        $entered = $cashAmount + $bankAmount;

                        if ($entered <= 0 || $entered - $remaining > $amountTolerance) {
                            throw new \Exception('Der Zahlungsbetrag darf den Restbetrag nicht ueberschreiten.');
                        }

                        if ($cashAmount > 0) {
                            $createdEntries[] = ReservationPaymentEntry::create([
                                'res_payment_id' => $paymentHeader->id,
                                'amount' => $cashAmount,
                                'method' => 'Bar',
                                'type' => 'final',
                                'transaction_date' => Carbon::now(),
                            ]);
                        }

                        if ($bankAmount > 0) {
                            $createdEntries[] = ReservationPaymentEntry::create([
                                'res_payment_id' => $paymentHeader->id,
                                'amount' => $bankAmount,
                                'method' => 'Bank',
                                'type' => 'final',
                                'transaction_date' => Carbon::now(),
                            ]);
                        }
                    } else {
                        $amount = (float)($request->payment_amount[$key] ?? 0);
                        $method = $request->payment_method[$key] ?? 'Bar';
                        if ($amount <= 0 || $amount - $remaining > $amountTolerance) {
                            throw new \Exception('Der Zahlungsbetrag darf den Restbetrag nicht ueberschreiten.');
                        }

                        $createdEntries[] = ReservationPaymentEntry::create([
                            'res_payment_id' => $paymentHeader->id,
                            'amount' => $amount,
                            'method' => $method,
                            'type' => 'final',
                            'transaction_date' => Carbon::now(),
                        ]);
                    }

                    if (!empty($createdEntries)) {
                        $this->invoiceService->generateInvoice($reservation, $createdEntries);
                    }
                }

                // Update payment header status
                $totalPaid = $paymentHeader->entries()->where('status', 'active')->sum('amount');
                if ($totalPaid >= $grossTotal - $amountTolerance) {
                    $paymentHeader->update(['status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $paymentHeader->update(['status' => 'partial']);
                } else {
                    $paymentHeader->update(['status' => 'unpaid']);
                }

                // Update dog's default plan based on stay length
                if ($reservation->dog) {
                    if ($days > 1) {
                        $reservation->dog->reg_plan = $planId;
                    } else {
                        $reservation->dog->day_plan = $planId;
                    }
                    $reservation->dog->save();
                }

                DB::commit();

                // HelloCash at checkout (cash entries only)
                $customerId = $reservation->dog->customer_id ?? null;
                $sendToHelloCash = $customerId && isset($helloCashCustomers[$customerId]) && $helloCashCustomers[$customerId] == '1';
                if ($sendToHelloCash) {
                    $cashEntries = $paymentHeader->entries()->where('status', 'active')->where('method', 'Bar')->get();
                    $cashTotal = $cashEntries->sum('amount');
                    if ($cashTotal > $amountTolerance) {
                        $items = [];
                        foreach ($cashEntries as $entry) {
                            $label = $entry->type === 'advance' ? 'Anzahlung' : 'Zahlung';
                            $items[] = [
                                'item_name' => $label,
                                'item_quantity' => 1.0,
                                'item_price' => round((float)$entry->amount, 2),
                                'item_taxRate' => (float)$vatPercentage,
                            ];
                        }

                        $this->hellocashService->createCashPaymentInvoice([
                            'reservation_id' => $reservation->id,
                            'customer_id' => $reservation->dog?->customer_id,
                            'hellocash_customer_id' => $reservation->dog->customer?->hellocash_customer_id,
                            'items' => $items,
                            'payment_method' => 'Bar',
                        ]);
                    }
                }
            } catch (\Exception $e) {
                DB::rollBack();

                $reservation = Reservation::with('dog')->find($res_id);
                $dogName = $reservation && $reservation->dog ? $reservation->dog->name : 'Unbekannt';

                Log::error('Checkout error: ' . $e->getMessage(), [
                    'reservation_id' => $res_id,
                    'dog_name' => $dogName,
                    'trace' => $e->getTraceAsString()
                ]);

                $failures[] = [
                    'reservation_id' => $res_id,
                    'dog_name' => $dogName,
                    'error' => $e->getMessage()
                ];

                continue;
            }
        }

        $totalReservations = count($request->res_id);
        $successCount = $totalReservations - count($failures);

        if (!empty($failures)) {
            $failureCount = count($failures);
            Session::flash('warning', "{$failureCount} von {$totalReservations} Check-outs fehlgeschlagen. Details pruefen.");
            Session::flash('checkout_failures', $failures);
            if ($successCount > 0) {
                Session::flash('partial_success', "{$successCount} Check-outs erfolgreich abgeschlossen.");
            }
        } else {
            Session::flash('success', "Alle {$totalReservations} Check-outs erfolgreich abgeschlossen!");
        }

        return to_route('admin.dogs.in.rooms');
    }

    /**
     * Checkout all reservations in a group at once.
     * One payment entry on the group, one invoice for all dogs.
     */
    public function group_checkout(Request $request)
    {
        $request->validate([
            'group_id'       => 'required|exists:reservation_groups,id',
            'received_amount' => 'required|numeric|min:0',
            'gateway'        => 'required|in:Bar,Bank',
        ]);

        $amountTolerance = 0.01;
        $visitCounter = new VisitCounterService();

        DB::beginTransaction();
        try {
            $group = ReservationGroup::with(['reservations.dog.customer', 'reservations.plan', 'entries'])
                ->lockForUpdate()
                ->findOrFail($request->group_id);

            // Recalculate group total from actual stay days (checkout = today)
            $checkoutDate = Carbon::now()->startOfDay();
            $vatPercentage = Preference::get('vat_percentage', 20);
            $vatMode = config('app.vat_calculation_mode', 'exclusive');
            $groupGross = 0.0;

            foreach ($group->reservations as $res) {
                if ($res->status === Reservation::STATUS_CHECKED_OUT) {
                    continue;
                }

                $checkinDate = $res->checkin_date ? Carbon::parse($res->checkin_date)->startOfDay() : $checkoutDate;
                $daysDiff = $checkinDate->diffInDays($checkoutDate);
                $calculationMode = config('app.days_calculation_mode', 'inclusive');
                $days = ($daysDiff === 0) ? 1 : (($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff);

                $plan = $res->plan;
                if (! $plan) {
                    throw new \Exception('Kein Preisplan für Reservierung #' . $res->id);
                }

                $isFlatRate = (int) $plan->flat_rate === 1;
                $planCost = (float) $plan->price;
                if (! $isFlatRate) {
                    $planCost *= $days;
                }

                if ($vatMode === 'inclusive') {
                    $resGross = round($planCost, 2);
                } else {
                    $resGross = round($planCost * (1 + ($vatPercentage / 100)), 2);
                }

                $groupGross += $resGross;

                // Update per-reservation payment header
                $paymentHeader = $res->reservationPayment;
                if ($paymentHeader) {
                    $paymentHeader->update(['total_due' => $resGross]);
                }
            }

            $groupGross = round($groupGross, 2);
            $group->update(['total_due' => $groupGross]);

            $advancePaid = (float) $group->activeEntries()->sum('amount');
            $remaining   = round($groupGross - $advancePaid, 2);
            $receivedNow = (float) $request->received_amount;

            $hadAdvanceBeforeCheckout = (float) $group->activeEntries()
                ->where('type', 'advance')
                ->sum('amount') > $amountTolerance;

            // Create final payment entry on the group if there's remaining balance
            $createdEntry = null;
            if ($remaining > $amountTolerance && $receivedNow > $amountTolerance) {
                $createdEntry = ReservationGroupEntry::create([
                    'reservation_group_id' => $group->id,
                    'amount'               => min($receivedNow, $remaining),
                    'method'               => $request->gateway,
                    'type'                 => 'final',
                    'transaction_date'     => Carbon::now(),
                    'note'                 => 'Restzahlung bei Gruppen-Checkout',
                    'status'               => 'active',
                ]);
            }

            $checkedOutReservationIds = [];

            // Mark all reservations as checked out
            foreach ($group->reservations as $res) {
                if ($res->status === Reservation::STATUS_CHECKED_OUT) {
                    continue;
                }

                if (! $res->visit_counted) {
                    $visitCounter->incrementVisit($res->dog_id);
                    $res->visit_counted = true;
                }
                if (! $res->days_counted && $res->checkin_date) {
                    $visitCounter->incrementDays($res->dog_id, $res->checkin_date, $checkoutDate);
                    $res->days_counted = true;
                }

                $res->update([
                    'checkout_date' => $checkoutDate->format('Y-m-d H:i:s'),
                    'status'        => Reservation::STATUS_CHECKED_OUT,
                ]);
                $checkedOutReservationIds[] = $res->id;

                if ($res->remote_pfotenstube_homepage_id) {
                    $this->homepageSyncService->updateStatus($res, 'abgeschlossen');
                }

                // Update per-reservation payment status
                $paymentHeader = $res->reservationPayment;
                if ($paymentHeader) {
                    $paymentHeader->update(['status' => 'paid']);
                }
            }

            $group->refreshStatus();

            // Schlussrechnung (checkout) for the balance payment; internal (final) rollup when an advance existed
            if ($createdEntry) {
                $group->refresh();
                $this->invoiceService->generateGroupInvoice($group, $createdEntry, 'checkout');
                if ($hadAdvanceBeforeCheckout) {
                    $this->invoiceService->generateGroupInternalInvoice($group->fresh(['customer', 'reservations.dog', 'reservations.plan']));
                }
            }

            // Update customer balance
            $customerId = $group->customer_id;
            if ($customerId) {
                (new CustomerBalanceService())->getBalance($customerId);
            }

            DB::commit();

            $dogCount = count($checkedOutReservationIds);
            Session::flash('success', "Gruppen-Checkout erfolgreich: {$dogCount} Hunde ausgecheckt. Gesamtbetrag: " . number_format($groupGross, 2) . '€.');

            return to_route('admin.dogs.in.rooms');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Group checkout error: ' . $e->getMessage(), [
                'group_id' => $request->group_id,
                'trace'    => $e->getTraceAsString(),
            ]);
            Session::flash('error', 'Fehler beim Gruppen-Checkout: ' . $e->getMessage());
            return back();
        }
    }

    public function move_friendship(Request $request)
    {
        $request->validate([
            'dog_id' => 'required',
            'room_id' => 'required',
        ]);

        $dog_id = $request->dog_id;
        $room_id = $request->room_id;

        // $dogs = Reservation::where('room_id', $room_id)->where('dog_id', '!=', $dog_id)->where('status', 1)->get()->pluck('dog_id');
        $dogs = Reservation::where('room_id', $room_id)->where('status', 1)->get()->pluck('dog_id');
        if(count($dogs) > 0)
        {
            foreach($dogs as $dog)
            {

                foreach($dogs as $obj)
                {
                    if($dog == $obj)
                    {
                        continue;
                    }

                    $is_friend = Friend::where([
                        ['dog_id', '=', $dog],
                        ['friend_id', '=', $obj],
                    ])
                    ->orWhere([
                        ['dog_id', '=', $obj],
                        ['friend_id', '=', $dog],
                    ])->first();

                    if(!$is_friend)
                    {
                        Friend::create([
                            'dog_id' => $obj,
                            'friend_id' => $dog
                        ]);
                    }

                }
            }
        }

        Session::flash('success', 'Hunde sind jetzt Freunde');
        return back();
    }

    /**
     * Create payment headers for reservation groups (same dates + plan).
     */
    private function createPaymentsForReservationGroups(array $reservationIds): void
    {
        if (empty($reservationIds)) {
            return;
        }

        $reservations = Reservation::with(['plan', 'reservationPayment'])
            ->whereIn('id', $reservationIds)
            ->orderBy('id')
            ->get();

        $reservations = $reservations->filter(function (Reservation $reservation) {
            return $reservation->reservationPayment === null;
        });

        if ($reservations->isEmpty()) {
            return;
        }

        // Each reservation gets its own payment record with its own total_due.
        foreach ($reservations as $reservation) {
            ReservationPayment::create([
                'res_id'    => $reservation->id,
                'total_due' => $reservation->gross_total,
                'status'    => 'unpaid',
            ]);
        }
    }

    public function check_balance(Request $request)
    {
        $id = $request->id;
        $res = Reservation::with([
            'dog' => function ($query) {
                $query->with('day_plan_obj', 'reg_plan_obj');
            },
            'reservationPayment.entries',
            'reservationGroup',
        ])->find($id);

        // Get VAT settings from preferences
        $vatPercentage = Preference::get('vat_percentage', 20);
        $vatMode = config('app.vat_calculation_mode', 'exclusive');

        if ($res && $res->dog && $res->dog->customer_id) {
            $customer_id = $res->dog->customer_id;

            // Use customer balance column (fast and accurate)
            $balanceService = new CustomerBalanceService();
            $balance = $balanceService->getBalance($customer_id);

            $paymentHeader = $res->reservationPayment;
            $totalPaid = $paymentHeader ? $paymentHeader->entries()->where('status', 'active')->sum('amount') : 0;
            $totalDue = $paymentHeader ? (float) $paymentHeader->total_due : (float) $res->gross_total;

            [$totalPaid, $remaining] = $this->mergeGroupPaymentIntoPaymentSummary($res, (float) $totalPaid, $totalDue);

            return [
                'total' => round($balance, 2),
                'doc' => $res,
                'vat_percentage' => $vatPercentage,
                'vat_calculation_mode' => $vatMode,
                'payment_summary' => [
                    'total_paid' => $totalPaid,
                    'total_due' => round($totalDue, 2),
                    'remaining' => $remaining,
                ],
            ];
        }

        $paymentHeader = $res?->reservationPayment;
        $totalPaid = $paymentHeader ? $paymentHeader->entries()->where('status', 'active')->sum('amount') : 0;
        $totalDue = $paymentHeader ? (float) $paymentHeader->total_due : (float) ($res?->gross_total ?? 0);

        [$totalPaid, $remaining] = $res
            ? $this->mergeGroupPaymentIntoPaymentSummary($res, (float) $totalPaid, $totalDue)
            : [round((float) $totalPaid, 2), round(max(0, $totalDue - (float) $totalPaid), 2)];

        return [
            'total' => 0,
            'doc' => $res,
            'vat_percentage' => $vatPercentage,
            'vat_calculation_mode' => $vatMode,
            'payment_summary' => [
                'total_paid' => $totalPaid,
                'total_due' => round($totalDue, 2),
                'remaining' => $remaining,
            ],
        ];
    }

    /**
     * Gruppenzahlungen liegen auf reservation_group_entries; die Checkout-Übersicht nutzt aber
     * nur reservation_payment_entries. Anteilige Zuordnung nach Verhältnis total_due zum Gruppen-Gesamtbetrag.
     *
     * @return array{0: float, 1: float} [combined_total_paid, remaining]
     */
    private function mergeGroupPaymentIntoPaymentSummary(Reservation $reservation, float $totalPaid, float $totalDue): array
    {
        if (! $reservation->reservation_group_id) {
            return [
                round($totalPaid, 2),
                round(max(0, $totalDue - $totalPaid), 2),
            ];
        }

        $reservation->loadMissing(['reservationGroup']);
        $group = $reservation->reservationGroup;
        if (! $group) {
            return [
                round($totalPaid, 2),
                round(max(0, $totalDue - $totalPaid), 2),
            ];
        }

        $groupPaid = (float) $group->activeEntries()->sum('amount');
        $groupDue = (float) $group->total_due;

        if ($groupDue <= 0.0001) {
            return [
                round($totalPaid, 2),
                round(max(0, $totalDue - $totalPaid), 2),
            ];
        }

        $allocatedFromGroup = round($groupPaid * ($totalDue / $groupDue), 2);
        $allocatedFromGroup = min($totalDue, $allocatedFromGroup);

        $combinedPaid = round($totalPaid + $allocatedFromGroup, 2);

        return [
            $combinedPaid,
            round(max(0, $totalDue - $combinedPaid), 2),
        ];
    }

    /**
     * Align reservation_payments.total_due with gross_total (plan × days, extras, VAT) after room assignment or check-in correction.
     *
     * @return array{previous_total_due: float, new_total_due: float, total_paid: float, credit_overpayment: float}
     */
    private function syncReservationPaymentAfterAssignment(Reservation $reservation): array
    {
        $reservation->refresh();
        $reservation->loadMissing(['plan', 'additionalCosts']);

        $newTotalDue = round((float) $reservation->gross_total, 2);
        $paymentHeader = $reservation->reservationPayment;

        $previousTotalDue = $paymentHeader ? (float) $paymentHeader->total_due : 0.0;

        if (! $paymentHeader) {
            $paymentHeader = ReservationPayment::create([
                'res_id'    => $reservation->id,
                'total_due' => $newTotalDue,
                'status'    => 'unpaid',
            ]);
            $reservation->setRelation('reservationPayment', $paymentHeader);
        } else {
            if ($previousTotalDue > $newTotalDue + 0.01) {
                $this->replaceActiveAdvancesAfterStayShortening($paymentHeader, $newTotalDue);
            }

            $totalPaid = (float) $paymentHeader->entries()->where('status', 'active')->sum('amount');
            $tol       = 0.01;
            $status    = 'unpaid';
            if ($totalPaid >= $newTotalDue - $tol) {
                $status = 'paid';
            } elseif ($totalPaid > $tol) {
                $status = 'partial';
            }
            $paymentHeader->update([
                'total_due' => $newTotalDue,
                'status'    => $status,
            ]);
        }

        $totalPaid = (float) $paymentHeader->entries()->where('status', 'active')->sum('amount');

        return [
            'previous_total_due'  => $previousTotalDue,
            'new_total_due'       => $newTotalDue,
            'total_paid'          => round($totalPaid, 2),
            'credit_overpayment' => round(max(0.0, $totalPaid - $newTotalDue), 2),
        ];
    }

    /**
     * When total_due drops, active advance lines may exceed the new service total. Cancel those lines (keep rows;
     * status cancelled) and their local PDFs, then add one advance entry for the corrected total so Kasse/checkout
     * use the same numbers as the stay.
     */
    private function replaceActiveAdvancesAfterStayShortening(ReservationPayment $paymentHeader, float $newTotalDue): void
    {
        $newTotalDue = round($newTotalDue, 2);

        $advances = $paymentHeader->entries()
            ->where('status', 'active')
            ->where('type', 'advance')
            ->orderBy('id')
            ->get();

        if ($advances->isEmpty()) {
            return;
        }

        $sumActiveAdvance = round((float) $advances->sum('amount'), 2);
        if ($sumActiveAdvance <= $newTotalDue + 0.01) {
            return;
        }

        $firstMethod = $advances->first()->method ?: 'Bar';

        foreach ($advances as $entry) {
            $entry->loadMissing('invoice');
            $invoice = $entry->invoice;
            if ($invoice && strtolower((string) $invoice->status) !== 'cancelled') {
                $invoice->update([
                    'status'               => 'cancelled',
                    'res_payment_entry_id' => null,
                ]);
            }

            $note = trim(($entry->note ?? '').' [Storniert: Aufenthalt nach Check-in angepasst]');
            $entry->update([
                'status' => 'cancelled',
                'note'   => $note,
            ]);
        }

        ReservationPaymentEntry::create([
            'res_payment_id'   => $paymentHeader->id,
            'amount'           => $newTotalDue,
            'method'           => $firstMethod,
            'type'             => 'advance',
            'transaction_date' => Carbon::now(),
            'note'             => 'Anzahlung nach korrigiertem Aufenthalt ('.$this->formatEuroDe($newTotalDue).' €)',
            'status'           => 'active',
        ]);
    }

    private function formatEuroDe(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }

    public function export(Request $request)
    {
        if (!General::permissions('Reservierung')) {
            return redirect()->route('admin.settings');
        }

        $keyword = $request->input('keyword', '');
        $status  = $request->input('status', [3]);
        $order   = $request->input('order', 'desc');
        $dateRange = $request->input('date_range', '');
        $dateFrom = $request->input('date_from', null);
        $dateTo = $request->input('date_to', null);

        // Parse date_range if provided (format: DD.MM.YYYY - DD.MM.YYYY or DD.MM.YYYY+-+DD.MM.YYYY)
        if ($dateRange && !$dateFrom && !$dateTo) {
            // Replace URL encoded spaces (+) with regular spaces
            $dateRange = str_replace('+', ' ', $dateRange);
            // Handle both " - " and "-" separators
            $dates = preg_split('/\s*-\s*/', $dateRange);
            if (count($dates) === 2) {
                try {
                    $dateFrom = Carbon::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d');
                    $dateTo = Carbon::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d');
                } catch (\Exception $e) {
                    // Invalid date format, ignore
                }
            }
        }

        // Build the query
        $query = Reservation::with(['plan', 'dog.customer'])
            ->when($keyword, function ($q) use ($keyword) {
                $q->whereHas('dog', function ($q2) use ($keyword) {
                    $q2->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('customer', function ($q3) use ($keyword) {
                            $q3->where('name', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($status && !in_array('all', (array)$status), function ($q) use ($status) {
                $q->whereIn('status', (array)$status);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('checkin_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('checkin_date', '<=', $dateTo);
            })
            ->orderBy('checkin_date', $order);

        // Export all filtered records
        $reservations = $query->get();
        $startIndex = 1;

        // Generate filename with timestamp
        $timestamp = date('Y-m-d_His');
        $filename = "Reservierung_{$timestamp}.xlsx";

        // Create Excel file using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set column headers with styling
        $headers = [
            '#',
            'Hund ID',
            'Hund Name',
            'Kunde',
            'Kunde ID Nummer',
            'Telefonnummer',
            'Preisplan',
            'Einchecken',
            'Auschecken',
            'Status'
        ];

        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $col++;
        }

        // Write data rows
        $row = 2;
        $index = $startIndex;
        
        foreach ($reservations as $r) {
            if (!$r->dog) {
                continue;
            }

            $statusText = '';
            if ($r->status == 1) {
                $statusText = 'Im Zimmer';
            } elseif ($r->status == 2) {
                $statusText = 'Kasse';
            } elseif ($r->status == 3) {
                $statusText = 'Reserviert';
            } elseif ($r->status == 4) {
                $statusText = 'Abgesagt';
            }

            $sheet->setCellValue('A' . $row, $index);
            $sheet->setCellValue('B' . $row, $r->dog->id);
            $sheet->setCellValue('C' . $row, $r->dog->name);
            $sheet->setCellValue('D' . $row, $r->dog->customer->name ?? '');
            $sheet->setCellValue('E' . $row, $r->dog->customer->id_number ?? '');
            $sheet->setCellValue('F' . $row, $r->dog->customer->phone ?? '');
            $sheet->setCellValue('G' . $row, $r->plan->title ?? '');
            $sheet->setCellValue('H' . $row, $r->checkin_date ? $r->checkin_date->format('d.m.Y') : '');
            $sheet->setCellValue('I' . $row, $r->checkout_date ? $r->checkout_date->format('d.m.Y') : '');
            $sheet->setCellValue('J' . $row, $statusText);

            $row++;
            $index++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to all cells with data
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:J' . ($row - 1))->applyFromArray($styleArray);

        // Create writer and generate file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

}
