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
use Carbon\Carbon;

use function PHPSTORM_META\type;

class ReservationsController extends Controller
{
    public function reservation(Request $request)
    {
        if(!General::permissions('Reservierung'))
        {
            return to_route('admin.settings');
        }

        if ($request->ajax()) {
            $keyword = isset($request->keyword) ? $request->keyword : "";
            $order = isset($request->order) ? $request->order : 'asc';

            $reservations = Reservation::with(['plan', 'dog.customer']);

            if (!empty($keyword)){
                $reservations = $reservations->whereHas('dog', function($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('customer', function ($query) use ($keyword) {
                            $query->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('phone', 'like', '%' . $keyword . '%');
                        });
                });
            }

            if (isset($request->status) && $request->status != 'all'){
                $stat = explode(',', $request->status);
                $reservations = $reservations->whereIn('status', $stat);
            }

            $reservations = $reservations->orderBy('checkin_date', $order)
            ->limit(30)
            ->get();

            return $reservations;
        }

        $status = false;
        if(isset($request->sl))
        {
            $status = $request->sl;
            $status = explode(',',$status);
        }

        $reservations = Reservation::with(['plan', 'dog' => function($query){
            $query->with('customer');
        }]);
        if($status)
        {
            $reservations = $reservations->whereIn('status', $status);
        }
        $reservations = $reservations->orderBy('checkin_date', 'desc')
        ->paginate(30);

        return view ("admin.reservation.index", compact('reservations'));
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
                    \Log::error('Date parsing error:', ['error' => $e->getMessage(), 'input' => $date]);
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

        $data = Reservation::find($request->id);
        if($data)
        {
            $data->delete();
            Session::flash('error', 'Reservierung erfolgreich gelöscht!');
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
            $data->status = 4;
            $data->save();
        }

        Session::flash('success', 'Reservierung erfolgreich abgebrochen!');

        return back();
    }

    public function edit_reservation($id)
    {
        if(!General::permissions('Reservierung'))
        {
            return to_route('admin.settings');
        }

        $reservation = Reservation::with('dog')->find($id);
        $reservation->res_date = Carbon::createFromFormat('Y-m-d H:i:s', $reservation->checkin_date)
            ->format('d/m/Y H:i').' - '.Carbon::createFromFormat('Y-m-d H:i:s', $reservation->checkout_date)
            ->format('d/m/Y H:i');
        // $reservations = Reservation::with('dog')->where('dog_id', $reservation->dog_id)->get();

        // foreach($reservations as $res)
        // {
        //     $res->res_date = Carbon::createFromFormat('Y-m-d H:i:s', $res->checkin_date)
        //     ->format('d/m/Y H:i').' - '.Carbon::createFromFormat('Y-m-d H:i:s', $res->checkout_date)
        //     ->format('d/m/Y H:i');
        // }

        return view ("admin.reservation.edit", compact('reservation'));
    }

    public function update_reservation(Request $request)
    {
        $request->validate([
            'dog_id' => 'required',
            'res_id' => 'required',
            'dates' => 'required',
        ]);

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

        // $dog = Dog::find($request->dog_id);
        // $old_reservations_count = count($reservations);
        // $new_reservations_count = count($request->dates);
        // $dates = $request->dates;

        // for($i=0;$i<$new_reservations_count;$i++)
        // {
        //     $date = $dates[$i];
        //     $ex = explode('-', $date);
        //     $ex[0] = trim($ex[0]);
        //     $ex[1] = trim($ex[1]);
        //     $ex[0] = str_replace('/','-',$ex[0]).' 00:05';
        //     $ex[1] = str_replace('/','-',$ex[1]).' 23:59';

        //     $checkin = Carbon::createFromFormat('m-d-Y H:i',$ex[0])->toDateTimeString();
        //     $checkout = Carbon::createFromFormat('m-d-Y H:i',$ex[1])->toDateTimeString();

        //     if($i < $old_reservations_count)
        //     {
        //         $reservation = Reservation::find($reservations[$i]->id);
        //         $reservation->checkin_date = $checkin;
        //         $reservation->checkout_date = $checkout;
        //         $reservation->save();
        //     }
        //     else{
        //         Reservation::create([
        //             'dog_id' => $request->dog_id,
        //             'checkin_date' => $checkin,
        //             'checkout_date' => $checkout,
        //             'status' => 3,
        //             'plan_id' => $dog->reg_plan,
        //         ]);
        //     }
        // }

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

        foreach($request->dates as $date)
        {
            $ex = explode('-', $date);
            $ex0 = $ex[0].' 00:05';
            $ex1 = $ex[1].' 23:59';
            $checkin = Carbon::createFromFormat('d/m/Y H:i', trim($ex0))->toDateTimeString();
            $checkout = Carbon::createFromFormat('d/m/Y H:i', trim($ex1))->toDateTimeString();

            Reservation::create([
                'dog_id' => $request->dog_id,
                'plan_id' => $request->plan_id,
                'checkin_date' => $checkin,
                'checkout_date' => $checkout,
                'room_id' => $request->room_id,
                'status' => 1,
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

        $room = Room::find($request->room_id);
        $capacity = (int)$room->capacity;

        // $reservations = Reservation::with('dog')->whereHas('dog')->where('room_id', $request->room_id)->where('status', 1)->count();

        $reservations = Reservation::where('room_id', $request->room_id)->where('status', 1)->count();

        if($reservations >= $capacity)
        {
            Session::flash('error','Der Raum ist bereits voll');
            return back();
        }

        // Reservation::where('dog_id', $request->dog_id)->update([
        //     'room_id' => $request->room_id,
        //     'status' => 1
        // ]);
        Reservation::where('id', $request->res_id)->update([
            'room_id' => $request->room_id,
            'status' => 1
        ]);

        Session::flash('success','Raum erfolgreich geändert!');
        return back();
    }

    public function fetch_reservation($id)
    {
        $reservation = Reservation::with(['plan','dog' => function($query){
            $query->with('customer');
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
            'received_amount' => 'required',
            'total' => 'required',
            'discount' => 'required',
            'gateway' => 'required',
            'status' => 'required',
            'checkout' => 'required',
        ]);

        $checkout = $request->checkout;
        $checkout = str_replace('/','-',$checkout);
        $checkout = date('Y-m-d H:i:s', strtotime($checkout));

        // Update reservation
        $reservation = Reservation::find($request->id);
        $reservation->checkout_date = $checkout;
        $reservation->plan_id = $request->price_plan;
        $reservation->status = 2;
        $reservation->save();

        // Create Price
        $plan = Plan::find($request->price_plan);
        $price = $plan->price;
        $cost = $price * $request->days;
        $discount_amount = 0;
        if($request->discount > 0)
        {
            $discount_amount = ($request->discount/100) * $cost;
        }

        if($request->received_amount >= $cost)
        {
            $status = 2;
        }
        elseif($request->received_amount < $cost && $request->received_amount > 0)
        {
            $status = 1;
        }
        else{
            $status = 0;
        }

        Payment::create([
            'res_id' => $request->id,
            'type' => $request->gateway,
            'cost' => $request->total,
            'received_amount' => $request->received_amount,
            'discount' => $request->discount,
            'discount_amount' => $discount_amount,
            'status' => $status
        ]);

        Session::flash('success', 'Kaufabwicklung erfolgreich');
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

        // check if new dogs are in the friend list
        $dog_id = $request->dog_id;
        // $dogs = Reservation::where('room_id', $room_id)->where('dog_id', '!=', $dog_id)->pluck('dog_id');
        $dogs = Reservation::where('room_id', $room_id)->where('status', 1)->get()->pluck('dog_id');
        $showModal = false;
        if(count($dogs) > 0)
        {
            foreach($dogs as $dog)
            {
                foreach($dogs as $obj)
                {
                    if($dog == $obj) continue;

                    $is_friend = Friend::where([
                        ['dog_id', '=', $obj],
                        ['friend_id', '=', $dog],
                    ])
                    ->orWhere([
                        ['dog_id', '=', $dog],
                        ['friend_id', '=', $obj],
                    ])
                    ->first();

                    if(!$is_friend)
                    {
                        $showModal = true;
                        break;
                    }
                }
            }
        }

        $res = Reservation::find($id);
        $res->room_id = $room_id;
        if(isset($request->status))
        {
            $res->status = $request->status;
        }
        $res->save();

        return [
            'showModal' => $showModal
        ];
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
        // ->groupBy('dog_id')
        ->orderBy('checkin_date', 'desc')->get();

        return view('admin.reservation.checkout', compact('reservations'));
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

        return view('admin.reservation.checkout', compact('reservations'));
    }

    public function dogs_in_rooms_update_checkout(Request $request)
    {
        if(count($request->res_id) == 0)
        {
            return back();
        }

        $now = date("Y-m-d H:i:s");

        foreach($request->res_id as $key => $res_id)
        {
            Reservation::where("id", $res_id)->update([
                "checkout_date" => $now,
                "status" => 2
            ]);

            $discount_percentage = $request->discount[$key];
            $discount_amount = 0;
            if($discount_percentage > 0)
            {
                $p1 = $request->invoice_amount[$key] * (1 - ($discount_percentage/100));
                $discount_amount = $request->invoice_amount[$key] - $p1;
            }

            Payment::create([
                "res_id" => $res_id,
                "type" => $request->payment_method[$key],
                "cost" => $request->invoice_amount[$key],
                "received_amount" => $request->received_amount[$key],
                "discount" => $request->discount[$key],
                "discount_amount" => $discount_amount,
                "status" => 1
            ]);
        }



        Session::flash("success","Aufzeichnen erfolgreich aktualisiert!");
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
        if($res)
        {
            $customer_id = $res->dog->customer_id;
            $records = \DB::select("select payments.received_amount as amount,payments.cost as cost, payments.discount as discount from payments,dogs,customers,reservations WHERE reservations.id=payments.res_id and reservations.dog_id=dogs.id and customers.id=dogs.customer_id and customers.id=$customer_id");
            $totalAmount = 0;

            foreach($records as $record)
            {
                $amount         = abs($record->amount);
                $cost           = abs($record->cost);

                $remaining = $amount - $cost;

                $totalAmount = $totalAmount+$remaining;
            }
            return [
                'total' => $totalAmount,
                'doc' => $res
            ];
        }

        return false;
    }
}
