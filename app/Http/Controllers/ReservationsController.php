<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Dog;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Payment;
use App\Models\Friend;
use App\Helpers\General;
use App\Services\VisitCounterService;
use App\Services\HomepageSyncService;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Preference;
use App\Services\CustomerBalanceService;
use App\Services\HelloCashService;
use App\Services\BankInvoiceService;
use App\Helpers\VATCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\HomepageReservationConfirmedMail;
use App\Mail\HomepageReservationCancelledMail;

use function PHPSTORM_META\type;

class ReservationsController extends Controller
{
    protected $homepageSyncService;
    protected $hellocashService;
    protected $bankInvoiceService;

    public function __construct(HelloCashService $hellocashService, BankInvoiceService $bankInvoiceService, HomepageSyncService $homepageSyncService)
    {
        $this->hellocashService = $hellocashService;
        $this->bankInvoiceService = $bankInvoiceService;
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
        return view ("admin.reservation.add", compact('dogs'));
    }

    public function add_reservation(Request $request)
    {
        $request->validate([
            'dog_ids' => 'required',
            'dates' => 'required',
        ]);

        $dogs = $request->dog_ids;
        foreach($dogs as $id)
        {
            $dog = Dog::find($id);
            $plan_id = $dog->reg_plan;
            if(!$plan_id)
            {
                Session::flash('error', 'Bitte aktualisieren Sie den Preisplan für Hunde, um eine Reservierung vorzunehmen');
                return to_route('admin.customers.edit_dog',['id' => $id]);
            }
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

                $res = Reservation::create([
                    'dog_id' => $id,
                    'checkin_date' => $checkin,
                    'checkout_date' => $checkout,
                    'plan_id' => $plan_id,
                    'status' => 3,
                ]);

            }
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

        $data = Reservation::with('payments')->find($request->id);
        if($data)
        {
            // Prevent deletion if reservation has payments (checked out reservations)
            if($data->payments && $data->payments->count() > 0)
            {
                Session::flash('error', 'Reservierung kann nicht gelöscht werden, da Zahlungen vorhanden sind.');
                return back();
            }
            
            // Push cancellation to Homepage if remote ID exists before deleting
            if ($data->remote_pfotenstube_homepage_id) {
                $this->homepageSyncService->updateStatus($data, 'storniert');
            }

            // Hard delete if no payments (not soft delete)
            $data->forceDelete();
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
            
            $data->status = 4;
            $data->save();

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

        return view('admin.reservation.homepage_pending', compact('reservations'));
    }

    public function homepage_pending_confirm(Request $request)
    {
        if (! General::permissions('Reservierung')) {
            return redirect()->route('admin.settings');
        }

        $request->validate(['id' => 'required|exists:reservations,id']);

        $reservation = Reservation::with('dog.customer')->findOrFail($request->id);
        if ((int) $reservation->status !== Reservation::STATUS_PENDING_CONFIRMATION
            || empty($reservation->remote_pfotenstube_homepage_id)) {
            Session::flash('error', 'Diese Anfrage kann nicht bestätigt werden.');

            return back();
        }

        $reservation->status = Reservation::STATUS_RESERVED;
        $reservation->save();

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

        $reservation->status = Reservation::STATUS_CANCELLED;
        $reservation->save();

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

            $reservations = $reservations->where('room_id', '!=', null)->where('status', 1)
            ->orderBy('checkin_date', 'desc')
            ->get();

            return $reservations;
        }

        $reservations = Reservation::with(['plan','room', 'dog' => function($query){
            $query->with('customer');
        }])
        ->where('room_id', '!=', null)
        ->where('status', 1)
        ->orderBy('checkin_date', 'desc')
        ->get();

        return view('admin.reservation.dogs-in-rooms' , compact('reservations'));
    }

    public function add_reservation_dashboard(Request $request)
    {
        $request->validate([
            'dog_id' => 'required',
            'plan_id' => 'required',
            'dates' => 'required',
            'room_id'=> 'required'
        ]);


        // Check room capacity and reservations
        $rom = Room::find($request->room_id);
        $capacity = (int)$rom->capacity;

        $reserved = Reservation::with('dog')->where('room_id', $rom->id)->where('status', 1)->get();
        $reserved_slots = count($reserved);

        if($reserved_slots >= $capacity)
        {
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

        foreach($request->dates as $date)
        {
            $ex = explode('-', $date);
            $ex0 = $ex[0].' 00:05';
            $ex1 = $ex[1].' 23:59';
            $checkin = Carbon::createFromFormat('d/m/Y H:i', trim($ex0))->toDateTimeString();
            $checkout = Carbon::createFromFormat('d/m/Y H:i', trim($ex1))->toDateTimeString();

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
        }

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
            
            $capacity = (int)$room->capacity;
            $reservations = Reservation::where('room_id', $request->room_id)->where('status', 1)->count();

            if($reservations >= $capacity)
            {
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

                if (!$checkinDate || $checkinDate->lt(Carbon::today())) {
                    $reservation->checkin_date = Carbon::now();
                }
                
                if (!$checkoutDate || $checkoutDate->lt(Carbon::today())) {
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

        $targetCapacity = (int)$targetRoom->capacity;
        $currentOccupancy = Reservation::where('room_id', $request->target_room_id)->where('status', 1)->count();
        $dogsToMove = count($request->reservation_ids);

        if (($currentOccupancy + $dogsToMove) > $targetCapacity) {
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
        $reservations = Reservation::with(['plan', 'payments'])
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
                
                if ($reservation->payments && $reservation->payments->count() > 0) {
                    $payment = $reservation->payments->first();
                    $actualAmount = $payment->received_amount;
                    
                    if ($payment->plan_cost && $duration > 0) {
                        $dailyPrice = $payment->plan_cost / $duration;
                    }
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
            'id' => 'required',
            'received_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'discount' => 'required',
            'gateway' => 'required|in:Bar,Bank',
            'status' => 'required|in:0,1,2',
            'checkout' => 'required',
            'wallet_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate and parse checkout date
        try {
            $checkoutInput = $request->checkout;
            $checkoutInput = str_replace('/', '-', $checkoutInput);
            $checkoutParsed = Carbon::parse($checkoutInput);
            
            if ($checkoutParsed->isFuture()) {
                Session::flash('error', 'Check-out Datum kann nicht in der Zukunft liegen.');
                return back()->withInput();
            }
            
            $checkout = $checkoutParsed->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Session::flash('error', 'Ungültiges Check-out Datumsformat.');
            return back()->withInput();
        }

        // Create Price
        $plan = Plan::find($request->price_plan);
        $days = max(1, (int)$request->days);
        
        // For flat rate plans, don't multiply by days
        $isFlatRate = $plan && $plan->flat_rate == 1;
        $planCost = $plan ? (float)$plan->price : 0.0;
        if (!$isFlatRate) {
            $planCost = $planCost * $days;
        }

        $special_cost = isset($request->special_cost) ? (float)$request->special_cost : 0.0;
        
        // Flat rate plans cannot have discounts
        $discountPercentage = $isFlatRate ? 0 : (int)$request->discount;
        $sendToHelloCash = $request->has('send_to_hellocash') && $request->send_to_hellocash == '1';
        
        $vatPercentage = Preference::get('vat_percentage', 20);
        $vatMode = config('app.vat_calculation_mode', 'exclusive');

        // Calculate net total based on VAT calculation mode
        $discount_amount = 0.0;
        if ($vatMode === 'inclusive') {
            // Prices include VAT, extract net from gross prices
            $planNet = VATCalculator::getNetFromGross($planCost, $vatPercentage);
            $specialNet = VATCalculator::getNetFromGross($special_cost, $vatPercentage);
            $netTotal = $planNet + $specialNet;
        } else {
            // Prices are net (VAT exclusive)
            $netTotal = $planCost + $special_cost;
        }
        
        // Apply discount to net total
        if ($discountPercentage > 0) {
            $discount_amount = ($discountPercentage / 100) * $netTotal;
            $discount_amount = round($discount_amount, 2);
            $netTotal = $netTotal - $discount_amount;
        }

        // Calculate VAT and gross total
        $vatAmount = VATCalculator::calculateVATAmount($netTotal, $vatPercentage);
        $grossTotal = $netTotal + $vatAmount;

        // Invoice total from request is gross total (includes VAT)
        $invoiceTotal = (float)$request->total;
        $calculatedGrossTotal = round((float)$grossTotal, 2);
        if (abs($invoiceTotal - $calculatedGrossTotal) > 0.009) {
            // Manual invoice override: derive net/VAT from manually entered gross amount.
            $planCost = round(max(0, $invoiceTotal - $special_cost), 2);
            $discountPercentage = 0;
            $discount_amount = 0.0;
            $netTotal = round(VATCalculator::getNetFromGross($invoiceTotal, $vatPercentage), 2);
            $vatAmount = round($invoiceTotal - $netTotal, 2);
        }
        $receivedAmount = (float)$request->received_amount;
        $useWallet = $request->has('use_wallet') && $request->use_wallet == '1';
        
        // Load reservation first to get customer ID (before transaction)
        $reservation = Reservation::with('dog')->find($request->id);
        if (!$reservation) {
            return response()->json(['error' => 'Reservierung nicht gefunden'], 404);
        }
        
        // Get customer ID for balance operations (before transaction for initial check)
        $customerId = $reservation->dog->customer_id ?? null;
        $balanceService = new CustomerBalanceService();
        $cashReceived = $receivedAmount;
        
        // Validate invoice total is not negative
        if ($invoiceTotal < 0) {
            Session::flash('error', 'Rechnungsbetrag darf nicht negativ sein');
            return back();
        }
        
        DB::beginTransaction();
        try {
            // Lock reservation to prevent race conditions (reload with lock and relationship)
            $reservation = Reservation::with('dog')->lockForUpdate()->find($request->id);
            if (!$reservation) {
                throw new \Exception('Reservierung nicht gefunden');
            }
            
            // Re-get customer ID after lock (in case it changed)
            $customerId = $reservation->dog->customer_id ?? null;
            
            // Calculate wallet amount INSIDE transaction after lock to prevent race conditions
            $walletAmount = 0;
            if ($useWallet && $customerId) {
                // Lock customer row to prevent race conditions
                $customer = Customer::where('id', $customerId)->lockForUpdate()->first();
                if (!$customer) {
                    throw new \Exception('Kunde nicht gefunden');
                }
                
                // Get current balance after lock (prevents race condition)
                $customerBalance = $balanceService->getBalance($customerId);
                
                // Calculate wallet amount based on available balance
                $requestedWallet = $customerBalance > 0 ? min($customerBalance, $invoiceTotal) : 0;
                
                // Validate that frontend wallet amount doesn't exceed available balance
                // This prevents overuse if balance changed between frontend calculation and backend processing
                if ($request->has('wallet_amount')) {
                    $frontendWalletAmount = (float)$request->wallet_amount;
                    if ($frontendWalletAmount > $requestedWallet) {
                        throw new \Exception('Guthaben reicht nicht aus. Verfügbar: ' . number_format($requestedWallet, 2) . '€');
                    }
                    $walletAmount = $frontendWalletAmount;
                } else {
                    $walletAmount = $requestedWallet;
                }
            }
            
            // Increment visit and days count on checkout (status = 2)
            if (!$reservation->visit_counted || !$reservation->days_counted) {
                $visitCounter = new VisitCounterService();
                
                // Increment visit count if not already counted
                if (!$reservation->visit_counted) {
                    $visitCounter->incrementVisit($reservation->dog_id);
                }
                
                // Increment days count if not already counted
                if (!$reservation->days_counted && $reservation->checkin_date) {
                    $checkinDate = Carbon::parse($reservation->checkin_date);
                    $checkoutDate = Carbon::parse($checkout);
                    
                    // Validate checkout date is not in the future
                    if ($checkoutDate->isFuture()) {
                        throw new \Exception('Check-out-Datum darf nicht in der Zukunft liegen');
                    }
                    
                    // Validate checkout date is not before checkin date (normalize to start of day for comparison)
                    // Same-day checkin/checkout is allowed (counts as 1 day)
                    $checkinDay = $checkinDate->copy()->startOfDay();
                    $checkoutDay = $checkoutDate->copy()->startOfDay();
                    if ($checkoutDay->lt($checkinDay)) {
                        throw new \Exception('Check-out-Datum darf nicht vor dem Check-in-Datum liegen');
                    }
                    
                    $visitCounter->incrementDays($reservation->dog_id, $checkinDate, $checkoutDate);
                }
            }
            
            // Update reservation
            $reservation->checkout_date = $checkout;
            $reservation->plan_id = $request->price_plan;
            $reservation->status = 2;
            $reservation->visit_counted = true;
            $reservation->days_counted = true;
            $reservation->save();

            // Sync with Homepage if it's a remote reservation
            if ($reservation->remote_pfotenstube_homepage_id) {
                // Status 2 means the reservation is completed
                $this->homepageSyncService->updateStatus($reservation, 'abgeschlossen');
            }

            // Update dog's default plan based on stay length
            if ($reservation->dog) {
                if ($days > 1) {
                    $reservation->dog->reg_plan = $request->price_plan;
                } else {
                    $reservation->dog->day_plan = $request->price_plan;
                }
                $reservation->dog->save();
            }
            
            // Determine status: if invoice is 0, automatically mark as paid
            $paymentStatus = $request->status;
            if ($invoiceTotal < 0.01) {
                $paymentStatus = 1; // Bezahlt (invoice amount is 0)
            }
            
            // Create payment record
            $payment = Payment::create([
                'res_id' => $request->id,
                'type' => $request->gateway,
                'plan_cost' => $planCost,
                'special_cost' => $special_cost,
                'days' => $days,
                'cost' => $invoiceTotal,
                'vat_amount' => round($vatAmount, 2),
                'net_amount' => round($netTotal, 2),
                'discount' => $discountPercentage,
                'discount_amount' => $discount_amount,
                'received_amount' => $cashReceived + $walletAmount,
                'remaining_amount' => 0, // Will be updated after settlement calculation
                'advance_payment' => 0, // Will be updated after settlement calculation
                'wallet_amount' => $walletAmount,
                'status' => $paymentStatus
            ]);

            // Use debt settlement logic to properly calculate payment allocation
            if ($customerId) {
                // Customer is already locked above (for wallet calculation), reuse the lock
                // If wallet wasn't used, lock customer now
                if (!$useWallet) {
                    $customer = Customer::where('id', $customerId)->lockForUpdate()->first();
                    if (!$customer) {
                        throw new \Exception('Kunde nicht gefunden');
                    }
                }
                
                $settlement = $balanceService->settlePaymentWithDebtConsideration(
                    $customerId,
                    $invoiceTotal,
                    $cashReceived,
                    $walletAmount,
                    $payment->id,
                    true
                );
                
                // Update status if fully settled (effective remaining is 0)
                $finalStatus = $paymentStatus;
                if ($settlement['remaining_amount'] < 0.01) {
                    $finalStatus = 1; // Bezahlt (fully settled)
                }
                
                $payment->update([
                    'remaining_amount' => $settlement['remaining_amount'] < 0.01 ? 0 : $settlement['remaining_amount'],
                    'advance_payment' => $settlement['advance_payment'] < 0.01 ? 0 : $settlement['advance_payment'],
                    'status' => $finalStatus
                ]);
                
                // Update customer balance using settlement details
                $balanceService->updateBalanceByChange($customerId, $settlement['balance_change']);
            } else {
                $effectiveReceived = $cashReceived + $walletAmount;
                $remainingAmount = $effectiveReceived >= $invoiceTotal ? 0 : round($invoiceTotal - $effectiveReceived, 2);
                $advancePayment = $effectiveReceived > $invoiceTotal ? round($effectiveReceived - $invoiceTotal, 2) : 0;
                
                // Update status if fully settled
                $finalStatus = $paymentStatus;
                if ($remainingAmount < 0.01) {
                    $finalStatus = 1; // Bezahlt (fully settled)
                }
                
                $payment->update([
                    'remaining_amount' => $remainingAmount < 0.01 ? 0 : $remainingAmount,
                    'advance_payment' => $advancePayment < 0.01 ? 0 : $advancePayment,
                    'status' => $finalStatus
                ]);
            }
            
            // Commit transaction
            DB::commit();
            
            $reservation->refresh();
            $payment->refresh();
            
            // Handle invoice generation based on payment method
            $invoiceResponse = null;
            if ($request->gateway === 'Bank') {
                // Generate Bank transfer invoice
                $invoiceResponse = $this->bankInvoiceService->generateInvoice(
                    $reservation,
                    $payment,
                    [
                        'plan_cost' => $planCost,
                        'special_cost' => $special_cost,
                        'discount_percentage' => $discountPercentage,
                        'days' => $days,
                    ]
                );
            } elseif ($sendToHelloCash) {
                // Handle HelloCash API call if requested
                $invoiceResponse = $this->handleHelloCashIntegration(
                    $request,
                    $plan,
                    $planCost,
                    $special_cost,
                    $days,
                    $discountPercentage,
                    $reservation->id,
                    $payment->id,
                );
            }

            // Link payment to invoice if available
            if ($invoiceResponse) {
                $localInvoiceId = $invoiceResponse['saved_invoice_id'] ?? null;
                if ($localInvoiceId) {
                    $payment->update(['invoice_id' => $localInvoiceId]);
                }
            }
            
            Session::flash('success', 'Kaufabwicklung erfolgreich');
            
            // If AJAX request, return JSON with invoice data
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kaufabwicklung erfolgreich',
                    'hellocash' => $invoiceResponse,
                    'invoice' => $invoiceResponse,
                ]);
            }
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Session::flash('error', 'Fehler bei der Kaufabwicklung: ' . $e->getMessage());
            Log::error('Checkout error: ' . $e->getMessage(), [
                'reservation_id' => $request->id,
                'customer_id' => $customerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // If AJAX request, return JSON error
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

            if(!$checkinDate || $checkinDate->lt(Carbon::today()))
            {
                $res->checkin_date = Carbon::now();
            }
            
            if(!$checkoutDate || $checkoutDate->lt(Carbon::today()))
            {
                $res->checkout_date = Carbon::now();
            }
        }
        
        $res->save();

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

        $reservations = Reservation::with(['plan','room', 'dog' => function($query){
            $query->with('customer','day_plan_obj');
        }])
        ->where('room_id', '!=', null)
        ->orderBy('checkin_date', 'desc')->get();

        // Group reservations by customer and fetch balances
        $groupedReservations = [];
        $balanceService = new CustomerBalanceService();
        
        foreach ($reservations as $reservation) {
            $customerId = $reservation->dog->customer_id ?? null;
            if ($customerId) {
                if (!isset($groupedReservations[$customerId])) {
                    $groupedReservations[$customerId] = [
                        'customer' => $reservation->dog->customer,
                        'balance' => $balanceService->getBalance($customerId),
                        'reservations' => []
                    ];
                }
                $groupedReservations[$customerId]['reservations'][] = $reservation;
            } else {
                // Handle reservations without customer
                if (!isset($groupedReservations[0])) {
                    $groupedReservations[0] = [
                        'customer' => null,
                        'balance' => 0,
                        'reservations' => []
                    ];
                }
                $groupedReservations[0]['reservations'][] = $reservation;
            }
        }

        $plans = Plan::orderBy('id', 'asc')->get();
        $vatPercentage = Preference::get('vat_percentage', 20);

        return view('admin.reservation.checkout', compact('groupedReservations', 'plans', 'vatPercentage'));
    }

    public function dogs_in_rooms_checkout_post(Request $request)
    {
        $request->validate([
            'entry' => 'required'
        ]);

        $entries = $request->entry;

        if(count($entries) === 0)
        {
            return back();
        }

        $reservations = Reservation::with(['plan', 'dog' => function($query){
            $query->with('customer');
        }])->whereIn('id', $entries)->get();

        // Group reservations by customer and fetch balances
        $groupedReservations = [];
        $balanceService = new CustomerBalanceService();
        
        foreach ($reservations as $reservation) {
            $customerId = $reservation->dog->customer_id ?? null;
            if ($customerId) {
                if (!isset($groupedReservations[$customerId])) {
                    $groupedReservations[$customerId] = [
                        'customer' => $reservation->dog->customer,
                        'balance' => $balanceService->getBalance($customerId),
                        'reservations' => []
                    ];
                }
                $groupedReservations[$customerId]['reservations'][] = $reservation;
            } else {
                // Handle reservations without customer (shouldn't happen, but safety)
                if (!isset($groupedReservations[0])) {
                    $groupedReservations[0] = [
                        'customer' => null,
                        'balance' => 0,
                        'reservations' => []
                    ];
                }
                $groupedReservations[0]['reservations'][] = $reservation;
            }
        }

        $plans = Plan::orderBy('id', 'asc')->get();
        $vatPercentage = Preference::get('vat_percentage', 20);

        return view('admin.reservation.checkout', compact('groupedReservations', 'plans', 'vatPercentage'));
    }

    public function dogs_in_rooms_update_checkout(Request $request)
    {
        if(count($request->res_id) == 0)
        {
            return back();
        }

        // Add comprehensive input validation
        $request->validate([
            'res_id' => 'required|array|min:1',
            'res_id.*' => 'required|integer|exists:reservations,id',
            'plan_id' => 'required|array',
            'plan_id.*' => 'required|integer|exists:plans,id',
            'days' => 'required|array',
            'days.*' => 'required|integer|min:1',
            'base_cost' => 'required|array',
            'base_cost.*' => 'required|numeric|min:0',
            'special_cost' => 'required|array',
            'special_cost.*' => 'required|numeric|min:0',
            'discount' => 'required|array',
            'discount.*' => 'required|numeric|min:0|max:100',
            'payment_method' => 'required|array',
            'payment_method.*' => 'required|string|in:Bar,Bank',
            'received_amount' => 'required|array',
            'received_amount.*' => 'required|numeric|min:0',
            'invoice_amount' => 'required|array',
            'invoice_amount.*' => 'required|numeric|min:0',
        ]);

        // Define amount tolerance constant for consistent use throughout
        $amountTolerance = 0.01;

        $visitCounter = new VisitCounterService();
        $balanceService = new CustomerBalanceService();
        
        // Initialize invoice data collection for grouping by customer
        $invoiceDataByCustomer = [];
        
        // Initialize HelloCash invoice data collection for Bar payments (per-customer)
        $helloCashDataByCustomer = [];
        $helloCashCustomers = $request->send_to_hellocash ?? [];
        
        // First pass: Calculate customer totals for wallet distribution
        $customerTotals = [];
        $customerReservationKeys = [];
        
        foreach($request->res_id as $key => $res_id) {
            $reservation = Reservation::with('dog')->find($res_id);
            if ($reservation && $reservation->dog) {
                $customerId = $reservation->dog->customer_id ?? null;
                if ($customerId) {
                    if (!isset($customerTotals[$customerId])) {
                        $customerTotals[$customerId] = 0;
                        $customerReservationKeys[$customerId] = [];
                    }
                    $invoiceTotal = floatval($request->invoice_amount[$key] ?? 0);
                    $customerTotals[$customerId] += $invoiceTotal;
                    $customerReservationKeys[$customerId][] = $key;
                }
            }
        }
        
        // Calculate wallet usage per customer
        $customerWalletUsage = [];
        $customerWalletAccumulated = []; // Track accumulated wallet per customer for rounding fix
        foreach ($customerTotals as $customerId => $total) {
            $useWallet = isset($request->use_wallet[$customerId]) && $request->use_wallet[$customerId] == '1';
            if ($useWallet && $customerId) {
                $customer = Customer::where('id', $customerId)->first();
                if ($customer) {
                    // Use the locked customer's balance directly instead of making a new query
                    $customerBalance = (float)($customer->balance ?? 0);
                    $walletToUse = $customerBalance > 0 ? min($customerBalance, $total) : 0;
                    $customerWalletUsage[$customerId] = $walletToUse;
                    $customerWalletAccumulated[$customerId] = 0; // Initialize accumulator
                }
            }
        }

        // Initialize failure tracking
        $failures = [];

        foreach($request->res_id as $key => $res_id)
        {
            DB::beginTransaction();
            try {
                // Lock reservation to prevent race conditions (load dog relationship for customer ID)
                $reservation = Reservation::with('dog')->lockForUpdate()->find($res_id);
                if (!$reservation) {
                    Log::error('Reservation not found for ID ' . $res_id);
                    throw new \Exception('Reservierung nicht gefunden');
                }
                
                // Prevent duplicate checkout of same reservation
                if ($reservation->status === 2) {
                    Log::error('Duplicate checkout attempt for reservation ID ' . $reservation->id);                
                    throw new \Exception('Diese Reservierung wurde bereits ausgecheckt');
                }
                
                // Validate dog exists and has customer
                if (!$reservation->dog) {
                    Log::error('Dog not found for reservation ID ' . $reservation->id);
                    throw new \Exception('Hund für diese Reservierung nicht gefunden');
                }
                
                // Get customer ID early for wallet operations
                $customerId = $reservation->dog->customer_id ?? null;
                
                // Handle custom checkout date (optional, defaults to current time)
                $checkoutDate = isset($request->checkout_date[$key]) && !empty($request->checkout_date[$key])
                    ? Carbon::parse($request->checkout_date[$key])->format('Y-m-d H:i:s')
                    : date("Y-m-d H:i:s");
                
                // Validate checkout date is not before checkin date
                if ($reservation->checkin_date) {
                    $checkinDate = Carbon::parse($reservation->checkin_date);
                    $checkoutDateCarbon = Carbon::parse($checkoutDate);
                    
                    if ($checkoutDateCarbon->isFuture()) {
                        Log::error('Checkout date is in the future for reservation ID ' . $reservation->id);
                        throw new \Exception('Check-out-Datum darf nicht in der Zukunft liegen');
                    }
                    
                    $checkinDay = $checkinDate->copy()->startOfDay();
                    $checkoutDay = $checkoutDateCarbon->copy()->startOfDay();
                    if ($checkoutDay->lt($checkinDay)) {
                        Log::error('Checkout date is before checkin date for reservation ID ' . $reservation->id);
                        throw new \Exception('Check-out-Datum darf nicht vor dem Check-in-Datum liegen');
                    }
                }
                
                // Increment visit and days count on checkout (status = 2) - INSIDE transaction
                if (!$reservation->visit_counted || !$reservation->days_counted) {
                    // Increment visit count if not already counted
                    if (!$reservation->visit_counted) {
                        $visitCounter->incrementVisit($reservation->dog_id);
                    }
                    
                    // Increment days count if not already counted
                    if (!$reservation->days_counted && $reservation->checkin_date) {
                        $checkinDate = Carbon::parse($reservation->checkin_date);
                        $checkoutDateCarbon = Carbon::parse($checkoutDate);
                        
                        $visitCounter->incrementDays($reservation->dog_id, $checkinDate, $checkoutDateCarbon);
                    }
                }

                // Get plan ID from request (optional, defaults to existing plan_id)
                $planId = isset($request->plan_id[$key]) ? (int)$request->plan_id[$key] : $reservation->plan_id;
                
                // Validate plan exists if provided
                if ($planId && !Plan::find($planId)) {
                    Log::error('Plan not found for ID ' . $planId . ' on reservation ID ' . $reservation->id);
                    throw new \Exception('Ausgewählter Preisplan existiert nicht');
                }

                $reservation->update([
                    "checkout_date" => $checkoutDate,
                    "status" => 2,
                    "plan_id" => $planId,
                    "visit_counted" => true,
                    "days_counted" => true
                ]);

                // Sync with Homepage if it's a remote reservation
                if ($reservation->remote_pfotenstube_homepage_id) {
                    $this->homepageSyncService->updateStatus($reservation, 'abgeschlossen');
                }

                $special_cost = isset($request->special_cost[$key]) ? floatval($request->special_cost[$key]) : 0;
                $base_cost = isset($request->base_cost[$key]) ? floatval($request->base_cost[$key]) : 0;
                $plan_cost = $base_cost; // plan_cost is the same as base_cost in bulk checkout
                
                // Get plan to check if it's flat rate
                $plan = Plan::find($planId);
                $isFlatRate = $plan && $plan->flat_rate == 1;
                
                // Flat rate plans cannot have discounts
                $discount_percentage = $isFlatRate ? 0 : (int)($request->discount[$key] ?? 0);
                $discount_amount = 0;
                
                // Calculate net total based on VAT calculation mode
                $vatPercentage = Preference::get('vat_percentage', 20);
                $vatMode = config('app.vat_calculation_mode', 'exclusive');
                
                if ($vatMode === 'inclusive') {
                    // Prices include VAT, extract net from gross prices
                    $baseNet = VATCalculator::getNetFromGross($base_cost, $vatPercentage);
                    $specialNet = VATCalculator::getNetFromGross($special_cost, $vatPercentage);
                    $netTotal = $baseNet + $specialNet;
                } else {
                    // Prices are net (VAT exclusive)
                    $netTotal = $base_cost + $special_cost;
                }
                
                // Calculate discount on net total
                if($discount_percentage > 0)
                {
                    $discount_amount = $netTotal * ($discount_percentage / 100);
                    $discount_amount = round($discount_amount, 2);
                    $netTotal = $netTotal - $discount_amount;
                }

                // Calculate VAT and gross total
                $vatAmount = VATCalculator::calculateVATAmount($netTotal, $vatPercentage);
                $grossTotal = $netTotal + $vatAmount;

                // Invoice total from request is gross total (includes VAT)
                $invoiceTotal = floatval($request->invoice_amount[$key]);
                $calculatedGrossTotal = round((float)$grossTotal, 2);
                if (abs($invoiceTotal - $calculatedGrossTotal) > 0.009) {
                    // Manual invoice override: derive net/VAT from manually entered gross amount.
                    $plan_cost = round(max(0, $invoiceTotal - $special_cost), 2);
                    $discount_percentage = 0;
                    $discount_amount = 0.0;
                    $netTotal = round(VATCalculator::getNetFromGross($invoiceTotal, $vatPercentage), 2);
                    $vatAmount = round($invoiceTotal - $netTotal, 2);
                }
                $receivedAmount = floatval($request->received_amount[$key]);
                
                // Validate invoice total is not negative
                if ($invoiceTotal < 0) {
                    Log::error('Invoice total is negative for reservation ID ' . $reservation->id);
                    throw new \Exception('Rechnungsbetrag darf nicht negativ sein');
                }
                
                // Validate received amount is not negative
                if ($receivedAmount < 0) {
                    Log::error('Received amount is negative for reservation ID ' . $reservation->id);
                    throw new \Exception('Erhaltener Betrag darf nicht negativ sein');
                }
                
                // Determine initial payment status based on amounts (same logic as single checkout)
                // Status: 0 = Nicht bezahlt, 1 = Bezahlt, 2 = Offen
                $paymentStatus = 1; // Default to paid
                if ($invoiceTotal < 0.01) {
                    // Invoice amount is 0 (e.g., organization plan), automatically paid
                    $paymentStatus = 1; // Bezahlt
                } elseif ($receivedAmount < 0.01) {
                    // No payment received
                    $paymentStatus = 0; // Nicht bezahlt
                } else {
                    // Payment received, check if it's partial or full
                    $remainingBeforeSettlement = $invoiceTotal - $receivedAmount;
                    if ($remainingBeforeSettlement > 0.01) {
                        // Partial payment - will be updated after settlement if fully settled
                        $paymentStatus = 2; // Offen (partially paid)
                    } else {
                        // Full payment (or overpayment)
                        $paymentStatus = 1; // Bezahlt
                    }
                }
                
                // Calculate wallet amount per customer (distributed proportionally)
                $walletAmount = 0;
                $cashReceived = $receivedAmount;
                
                // Check if wallet is enabled for this customer
                $useWallet = isset($request->use_wallet[$customerId]) && $request->use_wallet[$customerId] == '1';
                
                if ($useWallet && $customerId && isset($customerWalletUsage[$customerId])) {
                    // Distribute wallet proportionally across this customer's reservations
                    $customerTotal = $customerTotals[$customerId] ?? $invoiceTotal;
                    $totalWalletForCustomer = $customerWalletUsage[$customerId];
                    $walletProportion = $customerTotal > 0 ? ($invoiceTotal / $customerTotal) : 0;
                    
                    // Check if this is the last reservation for this customer to fix rounding
                    $customerResKeys = $customerReservationKeys[$customerId] ?? [];
                    $isLastReservation = (end($customerResKeys) == $key);
                    
                    if ($isLastReservation) {
                        // For last reservation, adjust to ensure sum equals total wallet
                        $walletAmount = $totalWalletForCustomer - ($customerWalletAccumulated[$customerId] ?? 0);
                    } else {
                        $walletAmount = $totalWalletForCustomer * $walletProportion;
                        $walletAmount = round($walletAmount, 2);
                        $customerWalletAccumulated[$customerId] = ($customerWalletAccumulated[$customerId] ?? 0) + $walletAmount;
                    }
                    
                    // Validate wallet amount is not negative and doesn't exceed what was calculated upfront
                    if ($walletAmount < 0) {
                        Log::error('Calculated wallet amount is negative for reservation ID ' . $reservation->id);
                        throw new \Exception('Guthabenbetrag darf nicht negativ sein');
                    }
                    
                    if ($walletAmount > $totalWalletForCustomer + 0.01) { // Allow small rounding tolerance
                        Log::error('Wallet amount exceeds total wallet for customer ID ' . $customerId);
                        throw new \Exception('Guthabenbetrag überschreitet verfügbares Guthaben');
                    }
                    
                    // Update received amount to include wallet
                    $receivedAmount = $cashReceived + $walletAmount;
                }
            
                // Calculate days from checkin to checkout
                $checkinDate = Carbon::parse($reservation->checkin_date)->startOfDay();
                $checkoutDateCarbon = Carbon::parse($checkoutDate)->startOfDay();
                $daysDiff = $checkinDate->diffInDays($checkoutDateCarbon);
                
                // Same-day checkin/checkout always counts as 1 day
                if ($daysDiff === 0) {
                    $days = 1;
                } else {
                    // Use days from request if provided, otherwise calculate
                    if (isset($request->days[$key]) && $request->days[$key] > 0) {
                        $days = max(1, (int)$request->days[$key]);
                    } else {
                        $calculationMode = config('app.days_calculation_mode', 'inclusive');
                        $days = ($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff;
                    }
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

                // Create payment record
                $payment = Payment::create([
                    "res_id" => $res_id,
                    "type" => $request->payment_method[$key],
                    "plan_cost" => $plan_cost,
                    "special_cost" => $special_cost,
                    "days" => $days,
                    "cost" => $invoiceTotal,
                    "vat_amount" => round($vatAmount, 2),
                    "net_amount" => round($netTotal, 2),
                    "received_amount" => $receivedAmount,
                    "discount" => $discount_percentage,
                    "discount_amount" => $discount_amount,
                    "remaining_amount" => 0, // Will be updated after settlement calculation
                    "advance_payment" => 0, // Will be updated after settlement calculation
                    "wallet_amount" => $walletAmount,
                    "status" => $paymentStatus
                ]);

                // Use debt settlement logic to properly calculate payment allocation
                if ($customerId) {
                    // Lock customer row INSIDE transaction to prevent race conditions
                    // Re-lock customer here (not from earlier wallet calculation) to ensure atomicity
                    $customer = Customer::where('id', $customerId)->lockForUpdate()->first();
                    if (!$customer) {
                        Log::error('Customer not found for ID ' . $customerId . ' on reservation ID ' . $reservation->id);
                        throw new \Exception('Kunde nicht gefunden');
                    }
                    
                    $settlement = $balanceService->settlePaymentWithDebtConsideration(
                        $customerId,
                        $invoiceTotal,
                        $cashReceived,
                        $walletAmount,
                        $payment->id,
                        true
                    );
                    
                    // Update status if fully settled (effective remaining is 0)
                    $finalStatus = $paymentStatus;
                    if ($settlement['remaining_amount'] < 0.01) {
                        $finalStatus = 1; // Bezahlt (fully settled)
                    }
                    
                    $payment->update([
                        'remaining_amount' => $settlement['remaining_amount'] < 0.01 ? 0 : $settlement['remaining_amount'],
                        'advance_payment' => $settlement['advance_payment'] < 0.01 ? 0 : $settlement['advance_payment'],
                        'status' => $finalStatus
                    ]);
                    
                    // Update customer balance using settlement details
                    $balanceService->updateBalanceByChange($customerId, $settlement['balance_change']);
                } else {
                    $remainingAmount = $receivedAmount >= $invoiceTotal ? 0 : round($invoiceTotal - $receivedAmount, 2);
                    $advancePayment = $receivedAmount > $invoiceTotal ? round($receivedAmount - $invoiceTotal, 2) : 0;
                    
                    // Update status if fully settled
                    $finalStatus = $paymentStatus;
                    if ($remainingAmount < 0.01) {
                        $finalStatus = 1; // Bezahlt (fully settled)
                    }
                    
                    $payment->update([
                        'remaining_amount' => $remainingAmount < 0.01 ? 0 : $remainingAmount,
                        'advance_payment' => $advancePayment < 0.01 ? 0 : $advancePayment,
                        'status' => $finalStatus
                    ]);
                }
                
                // Commit transaction for this reservation and payment
                DB::commit();
                
                // Collect invoice data for Bank payments (instead of generating immediately)
                if ($request->payment_method[$key] === 'Bank' && $customerId) {
                    // Group invoice data by customer
                    if (!isset($invoiceDataByCustomer[$customerId])) {
                        $invoiceDataByCustomer[$customerId] = [
                            'customer_id' => $customerId,
                            'customer' => $reservation->dog->customer,
                            'reservations' => [],
                            'reservation_ids' => [],
                        ];
                    }
                    
                    // Store invoice data for later grouped generation
                    $invoiceDataByCustomer[$customerId]['reservations'][] = [
                        'reservation' => $reservation,
                        'payment' => $payment,
                        'plan_cost' => $plan_cost,
                        'special_cost' => $special_cost,
                        'discount_percentage' => $discount_percentage,
                        'days' => $days,
                    ];
                    
                    // Collect reservation IDs for grouped invoice
                    $invoiceDataByCustomer[$customerId]['reservation_ids'][] = $res_id;
                }
                
                // Collect HelloCash data for Bar payments when send_to_hellocash is checked for this customer
                $customerHelloCashEnabled = isset($helloCashCustomers[$customerId]) && $helloCashCustomers[$customerId] == '1';
                if ($customerHelloCashEnabled && $request->payment_method[$key] === 'Bar' && $customerId) {
                    // Group HelloCash invoice data by customer
                    if (!isset($helloCashDataByCustomer[$customerId])) {
                        $customer = $reservation->dog->customer;
                        $helloCashDataByCustomer[$customerId] = [
                            'customer_id' => $customerId,
                            'customer' => $customer,
                            'hellocash_customer_id' => $customer->hellocash_customer_id ?? null,
                            'reservations' => [],
                            'reservation_ids' => [],
                            'payment_ids' => [],
                        ];
                    }
                    
                    // Store data for HelloCash grouped invoice - use form-calculated values
                    // Calculate net costs - discount applies to BOTH base_cost and special_cost
                    $baseCostNet = $vatMode === 'inclusive' 
                        ? VATCalculator::getNetFromGross($base_cost, $vatPercentage) 
                        : $base_cost;
                    $specialCostNet = $vatMode === 'inclusive' 
                        ? VATCalculator::getNetFromGross($special_cost, $vatPercentage) 
                        : $special_cost;
                    
                    // Apply discount to BOTH costs (same as frontend calculation)
                    $discountMultiplier = $discount_percentage > 0 ? (1 - $discount_percentage / 100) : 1;
                    $planCostNetAfterDiscount = $baseCostNet * $discountMultiplier;
                    $specialCostNetAfterDiscount = $specialCostNet * $discountMultiplier;
                    
                    $helloCashDataByCustomer[$customerId]['reservations'][] = [
                        'dog_name' => $reservation->dog->name ?? 'Hund',
                        'plan_title' => $plan->title ?? 'Hundepension',
                        'plan_cost_net' => round($planCostNetAfterDiscount, 2),
                        'special_cost_net' => round($specialCostNetAfterDiscount, 2),
                        'days' => $days,
                        'discount_percentage' => $discount_percentage,
                    ];
                    
                    // Collect IDs for linking
                    $helloCashDataByCustomer[$customerId]['reservation_ids'][] = $res_id;
                    $helloCashDataByCustomer[$customerId]['payment_ids'][] = $payment->id;
                }
            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();
                
                // Get reservation info for error reporting
                $reservation = Reservation::with('dog')->find($res_id);
                $dogName = $reservation && $reservation->dog ? $reservation->dog->name : 'Unbekannt';
                
                \Log::error('Bulk checkout error: ' . $e->getMessage(), [
                    'reservation_id' => $res_id,
                    'customer_id' => $customerId ?? null,
                    'dog_name' => $dogName,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Track failure for user feedback
                $failures[] = [
                    'reservation_id' => $res_id,
                    'dog_name' => $dogName,
                    'error' => $e->getMessage()
                ];
                
                // Continue with next reservation instead of failing entire bulk operation
                continue;
            }
        }

        // Generate grouped invoices after all reservations are processed
        $generatedInvoices = [];
        if (!empty($invoiceDataByCustomer)) {
            $bankInvoiceService = new BankInvoiceService();
            
            Log::info('Bulk checkout: Generating invoices for ' . count($invoiceDataByCustomer) . ' customer(s)');
            
            foreach ($invoiceDataByCustomer as $customerId => $invoiceData) {
                try {
                    // Generate single invoice for all reservations of this customer
                    $invoiceResponse = $bankInvoiceService->generateGroupedInvoice(
                        $invoiceData['customer'],
                        $invoiceData['reservations'],
                        $invoiceData['reservation_ids'] ?? []
                    );
                    
                    if ($invoiceResponse['success'] ?? false) {
                        $invoiceId = $invoiceResponse['invoice_id'] ?? null;
                        
                        // Link all payments to the invoice
                        if ($invoiceId) {
                            $paymentIds = collect($invoiceData['reservations'])
                                ->map(fn($item) => $item['payment']->id ?? null)
                                ->filter()
                                ->toArray();
                            if (!empty($paymentIds)) {
                                Payment::whereIn('id', $paymentIds)->update(['invoice_id' => $invoiceId]);
                            }
                        }
                        
                        $generatedInvoices[] = [
                            'customer_id' => $customerId,
                            'customer_name' => $invoiceData['customer']->name,
                            'invoice_id' => $invoiceId,
                            'invoice_url' => $invoiceId ? route('admin.invoices.view', $invoiceId) : null,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Grouped invoice generation failed during bulk checkout', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }
        
        // Generate HelloCash grouped invoices for Bar payments (when send_to_hellocash is checked)
        $helloCashInvoices = [];
        if (!empty($helloCashDataByCustomer)) {
            $helloCashService = new HelloCashService();
            
            Log::info('Bulk checkout: Generating HelloCash invoices for ' . count($helloCashDataByCustomer) . ' customer(s)');
            
            foreach ($helloCashDataByCustomer as $customerId => $helloCashData) {
                try {
                    // Prepare data for HelloCash grouped invoice
                    $invoiceResponse = $helloCashService->createGroupedInvoice([
                        'customer_id' => $customerId,
                        'hellocash_customer_id' => $helloCashData['hellocash_customer_id'],
                        'reservations' => $helloCashData['reservations'],
                        'reservation_ids' => $helloCashData['reservation_ids'],
                        'payment_ids' => $helloCashData['payment_ids'],
                        'payment_method' => 'Bar',
                    ]);
                    
                    if ($invoiceResponse['success'] ?? false) {
                        $savedInvoiceId = $invoiceResponse['saved_invoice_id'] ?? null;
                        
                        // Link all payments to the HelloCash invoice
                        if ($savedInvoiceId && !empty($helloCashData['payment_ids'])) {
                            Payment::whereIn('id', $helloCashData['payment_ids'])->update(['invoice_id' => $savedInvoiceId]);
                        }
                        
                        $helloCashInvoices[] = [
                            'customer_id' => $customerId,
                            'customer_name' => $helloCashData['customer']->name,
                            'invoice_id' => $savedInvoiceId,
                            'invoice_url' => $savedInvoiceId ? route('admin.invoices.view', $savedInvoiceId) : null,
                            'hellocash_invoice_id' => $invoiceResponse['invoice_id'] ?? null,
                        ];
                        
                        Log::info('HelloCash grouped invoice created for customer ' . $customerId, [
                            'hellocash_invoice_id' => $invoiceResponse['invoice_id'] ?? null,
                            'saved_invoice_id' => $savedInvoiceId,
                            'reservation_count' => count($helloCashData['reservations']),
                        ]);
                    } else {
                        Log::warning('HelloCash grouped invoice failed for customer ' . $customerId, [
                            'error' => $invoiceResponse['error'] ?? 'Unknown error',
                        ]);
                        
                        // Flash a warning but don't fail the checkout
                        Session::flash('hellocash_warning', 'Registrierkasse-Gruppenrechnung für ' . $helloCashData['customer']->name . ' konnte nicht erstellt werden: ' . ($invoiceResponse['error'] ?? 'Unbekannter Fehler'));
                    }
                } catch (\Exception $e) {
                    Log::error('HelloCash grouped invoice exception during bulk checkout', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    Session::flash('hellocash_warning', 'Registrierkasse-Fehler für ' . $helloCashData['customer']->name . ': ' . $e->getMessage());
                }
            }
        }
        
        // Merge both Bank and HelloCash invoices for display
        $allInvoices = array_merge($generatedInvoices, $helloCashInvoices);

        // Store invoice URLs for opening in new tabs
        if (!empty($allInvoices)) {
            Log::info('Bulk checkout: Storing ' . count($allInvoices) . ' invoice(s) in session', $allInvoices);
            Session::flash('bulk_checkout_invoices', $allInvoices);
        } else {
            Log::info('Bulk checkout: No invoices to store (Bar payment without HelloCash or no Bank payments)');
        }

        // Provide appropriate user feedback based on success/failure status
        $totalReservations = count($request->res_id);
        $successCount = $totalReservations - count($failures);
        
        if (!empty($failures)) {
            $failureCount = count($failures);
            Session::flash('warning', "{$failureCount} von {$totalReservations} Check-outs fehlgeschlagen. Details prüfen.");
            Session::flash('checkout_failures', $failures);
            
            if ($successCount > 0) {
                Session::flash('partial_success', "{$successCount} Check-outs erfolgreich abgeschlossen.");
            }
        } else {
            Session::flash("success", "Alle {$totalReservations} Check-outs erfolgreich abgeschlossen!");
        }
        
        return to_route("admin.dogs.in.rooms");
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

    public function check_balance(Request $request)
    {
        $id = $request->id;
        $res = Reservation::with(['dog' => function($query){
            $query->with('day_plan_obj', 'reg_plan_obj');
        }])->find($id);
        
        // Get VAT settings from preferences
        $vatPercentage = Preference::get('vat_percentage', 20);
        $vatMode = config('app.vat_calculation_mode', 'exclusive');
        
        if($res && $res->dog && $res->dog->customer_id)
        {
            $customer_id = $res->dog->customer_id;
            
            // Use customer balance column (fast and accurate)
            $balanceService = new CustomerBalanceService();
            $balance = $balanceService->getBalance($customer_id);

            return [
                'total' => round($balance, 2),
                'doc' => $res,
                'vat_percentage' => $vatPercentage,
                'vat_calculation_mode' => $vatMode,
            ];
        }

        return [
            'total' => 0,
            'doc' => $res,
            'vat_percentage' => $vatPercentage,
            'vat_calculation_mode' => $vatMode,
        ];
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

    /**
     * Handle HelloCash API integration for checkout
     */
    private function handleHelloCashIntegration(
        Request $request,
        Plan $plan,
        float $planCost,
        float $specialCost,
        int $days,
        int $discountPercentage,
        int $reservationId,
        int $paymentId
    ): ?array {
        if (!$request->has('send_to_hellocash') || $request->send_to_hellocash != '1') {
            return null;
        }

        try {
            $plan->refresh();
            
            $reservation = Reservation::with('dog.customer')->find($reservationId);
            $hellocashCustomerId = $reservation?->dog?->customer?->hellocash_customer_id ?? null;
            
            $hellocashResponse = $this->hellocashService->createInvoice([
                'plan' => $plan,
                'plan_cost' => $planCost,
                'days' => $days,
                'special_cost' => $specialCost,
                'discount_percent' => $discountPercentage,
                'payment_method' => $request->gateway ?? 'Bar',
                'reservation_id' => $reservationId,
                'payment_id' => $paymentId,
                'hellocash_customer_id' => $hellocashCustomerId,
            ]);
            
            if (!$hellocashResponse['success']) {
                $errorMessage = $hellocashResponse['error'] ?? 'Unknown error';
                Log::error('HelloCash API call failed', [
                    'reservation_id' => $request->id,
                    'error' => $errorMessage,
                ]);

                Session::flash(
                    'warning',
                    'Die Zahlung wurde erfolgreich verarbeitet, jedoch trat ein Fehler bei der Registrierkasse-Verarbeitung auf. Bitte versuchen Sie es erneut.'
                );
            }
            
            return $hellocashResponse;
        } catch (\Exception $e) {
            Log::error('HelloCash Service Exception', [
                'reservation_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Session::flash(
                'warning',
                'Die Zahlung wurde erfolgreich verarbeitet, jedoch trat ein technischer Fehler bei der Registrierkasse-Kommunikation auf. Bitte versuchen Sie es erneut.'
            );
            
            return null;
        }
    }
}
