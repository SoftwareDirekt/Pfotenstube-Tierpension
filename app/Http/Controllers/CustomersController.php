<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Session;
use App\Models\Plan;
use App\Models\Dog;
use App\Models\Visit;
use App\Models\Pickup;
use App\Models\Friend;
use App\Models\Reservation;
use App\Models\Payment;
use App\Helpers\General;
use DB;

class CustomersController extends Controller
{
    public function customers(Request $request)
    {
        if(!General::permissions('Kunde'))
        {
            return to_route('admin.settings');
        }

        if($request->ajax())
        {
            $keyword = isset($request->keyword) ? $request->keyword :"";
            $order = isset($request->order) ? $request->order : 'asc';

            $where = [];
            $where = function ($query) {};

            if (isset($request->keyword) && !empty($request->keyword)) {
                $keyword = $request->keyword;
                $where = function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                    $query->orWhere('id_number', 'like', '%' . $keyword . '%');
                        // ->orWhere('email', 'like', '%' . $keyword . '%')
                        // ->orWhere('phone', 'like', '%' . $keyword . '%');
                };
            } else {
                $where = null;
            }

            $customersQuery = Customer::with(['dogs' => function($query) {
                $query->where('status', 1);
                $query->with('reg_plan_obj', 'day_plan_obj');
            }]);

            if ($where) {
                $customersQuery->where($where);
            }

            if (isset($keyword) && !empty($keyword)) {
                $customersQuery->orWhereHas('dogs', function ($query) use ($keyword) {
                    $query->where('dogs.name','like', '%' . $keyword . '%');
                });
            }

            $customers = $customersQuery->orderBy('id', $order)->limit(30)->get();

            return $customers;
        }

        $customers = Customer::with(['dogs' => function($query){
            $query->where('status', 1);
            $query->with('reg_plan_obj','day_plan_obj');
        }])->orderBy('id', 'DESC')->paginate(30);


        return view('admin.customer.list', compact('customers'));
    }

    public function add_customers()
    {
        if(!General::permissions('Kunde'))
        {
            return to_route('admin.settings');
        }

        $plans = Plan::get();
        $dogs = Dog::with('customer')->get();

        return view ('admin.customer.add', compact('plans', 'dogs'));
    }

    public function post_customers(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|email|unique:customers',
            'phone' => 'nullable|max:50',
            'id_number' => 'nullable|unique:customers',
        ]);

        $photo = 'no-user-picture.gif';

        if(isset($request->picture) && $request->picture != null)
        {
            $picture = $request->picture;
            $picture_name = time().$picture->getClientOriginalName();
            $picture->move(public_path('uploads/users'), $picture_name);
            $photo = $picture_name;
        }

        $customer = Customer::create([
            'type' => $request->type,
            'title' => $request->title,
            'profession' => $request->profession,
            'name' => $request->name,
            'email' => $request->email,
            'street' => $request->street,
            'city' => $request->city,
            'zipcode' => $request->postcode,
            'country' => $request->country,
            'phone' => $request->phone,
            'emergency_contact' => $request->emergency_contact,
            'veterinarian' => $request->veterinarian,
            'id_number' => $request->id_number,
            'picture' => $photo,
        ]);

        // Add Dogs
        if(!isset($request->dogs))
        {
            Session::flash('success', 'Der Kunde wurde erfolgreich hinzugefügt');
            return back();
        }

        foreach($request->dogs as $dog)
        {
            $photo = 'no-user-picture.gif';

            if(isset($dog['picture']) && $dog['picture'] != null)
            {
                $picture = $dog['picture'];
                $picture_name = time().$picture->getClientOriginalName();
                $picture->move(public_path('uploads/users/dogs'), $picture_name);
                $photo = $picture_name;
            }

            $is_medication = isset($dog['is_medication']) ? 1 : 0;
            $is_eating_habits = isset($dog['is_eating_habits']) ? 1 : 0;
            $is_special_eating = isset($dog['is_special_eating']) ? 1 : 0;
            $is_allergy = isset($dog['is_allergy']) ? 1 : 0;
            $water_lover = isset($dog['water_lover']) ? 1 : 0;

            $dog_db = Dog::create([
                'customer_id'=> $customer->id,
                'name'=> $dog['name'],
                'picture' => $photo,
                'age' => $dog['age'],
                'neutered' => (int)$dog['neutered'],
                'compatible_breed' => $dog['race'],
                'is_allergy' => $is_allergy,
                'allergy' => $dog['allergy'],
                'chip_number' => $dog['chip_number'],
                'is_medication' => $is_medication,
                'medication' => $dog['medication'],
                'health_problems' => $dog['health_problems'],
                'morgen' => $dog['morgen'],
                'mittag' => $dog['mittag'],
                'abend' => $dog['abend'],
                'compatibility' => $dog['compatibility'],
                'water_lover' => $water_lover,
                'is_eating_habits' => $is_eating_habits,
                'eating_morning' => $dog['eating_morning'],
                'eating_midday' => $dog['eating_midday'],
                'eating_evening' => $dog['eating_evening'],
                'is_special_eating' => $is_special_eating,
                'special_morning' => $dog['special_morning'],
                'special_midday' => $dog['special_midday'],
                'special_evening' => $dog['special_evening'],
                'gender' => $dog['gender'],
                'weight' => $dog['gender'],
                'reg_plan' => $dog['price_plan'],
                'day_plan' => $dog['daily_rate'],
            ]);

            // Saving dog vists to db
            $visits = (isset($dog['visits']) && $dog['visits'] != null) ? $dog['visits'] : 0;
            $stay = (isset($dog['days']) && $dog['days'] != null) ? $dog['days'] : 0;

            Visit::create([
                'dog_id' => $dog_db->id,
                'visits' => $visits,
                'stay' => $stay
            ]);

            // Save Pickups
            if(isset($dog['picks']) && count($dog['picks']) > 0)
            {
                foreach($dog['picks'] as $pick)
                {
                    $filename = null;
                    $picture = isset($pick['file']) ? $pick['file'] : false;
                    if($picture)
                    {
                        $picture_name = time().$picture->getClientOriginalName();
                        $picture->move(public_path('uploads/users/pickup'), $picture_name);
                        $filename = $picture_name;
                    }

                    Pickup::create([
                        'dog_id' => $dog_db->id,
                        'name' => $pick['name'],
                        'phone' => $pick['phone'],
                        'id_number'=> $pick['id'],
                        'picture'=> $filename,
                    ]);
                }
            }

            //saving dog friends to db
            if(isset($dog['dog_friends']) && count($dog['dog_friends']) > 0)
            {
                foreach($dog['dog_friends'] as $friend_id)
                {
                    Friend::create([
                        'dog_id' => $dog_db->id,
                        'friend_id' => $friend_id
                    ]);
                }
            }
        }

        Session::flash('success', 'Der Kunde wurde erfolgreich hinzugefügt');
        return back();

    }
    public function edit_customers($id)
    {
        if(!General::permissions('Kunde'))
        {
            return to_route('admin.settings');
        }

        $customer = Customer::find($id);
        return view('admin.customer.edit' , compact('customer'));
    }

    public function update_customers(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'id_number' => 'required',
        ]);

        $customer = Customer::find($request->id);
        $customer->type = $request->type;
        $customer->title = $request->title;
        $customer->profession = $request->profession;
        $customer->name = $request->name;
        $customer->street = $request->street;
        $customer->city = $request->city;
        $customer->zipcode = $request->postcode;
        $customer->country = $request->country;
        $customer->phone = $request->phone;
        $customer->emergency_contact = $request->emergency_contact;
        $customer->veterinarian = $request->veterinarian;
        $customer->id_number = $request->id_number;

        if(isset($request->picture) && $request->picture != null)
        {
            $picture = $request->picture;
            $picture_name = time().$picture->getClientOriginalName();
            $picture->move(public_path('uploads/users'), $picture_name);
            $customer->picture = $picture_name;
        }

        $customer->save();

        Session::flash('success', 'Der Kunde wurde erfolgreich aktualisiert');
        return to_route('admin.customers');
    }

    public function delete_customers(Request $request)
    {
        Customer::where('id', $request->id)->delete();

        Session::flash('error','Kunde erfolgreich gelöscht');
        return back();
    }

    public function customer_preview(Request $request, $id)
    {
        if(!General::permissions('Kunden ansehen'))
        {
            return to_route('admin.settings');
        }

        // $dogs = Dog::with(['visit'])->where('customer_id', $id)->orderBy('id' , 'desc')->get();

        $customer = Customer::find($id);
        if(!$customer)
        {
            return to_route('admin.customers');
        }

        // Get customer payments
        // Get Customer dogs
        $payments = [];
        $dogs = Dog::where('customer_id', $id)->orderBy('id' , 'desc')->get();
        if(count($dogs) > 0)
        {
            foreach($dogs as $item)
            {
                $reservations = Reservation::where('dog_id', $item->id)->get();
                if(count($reservations) > 0)
                {
                    foreach($reservations as $reservation)
                    {
                        $payments_raw = Payment::where('res_id', $reservation->id)->get();
                        if(count($payments_raw) > 0)
                        {
                            foreach($payments_raw as $payment)
                            {
                                $payment->dog = $item->name;
                                $payment->dog_id = $item->id;
                                $payment->checkin = $reservation->checkin_date;
                                $payment->checkout = $reservation->checkout_date;

                                // Get Remaining Amount
                                // $records = \DB::select("select payments.received_amount as amount,payments.cost as cost, payments.discount as discount from payments,dogs,customers,reservations WHERE reservations.id=payments.res_id and reservations.dog_id=dogs.id and customers.id=dogs.customer_id and customers.id=$id");
                                // $totalAmount = 0;
                                // foreach($records as $record)
                                // {
                                //     $amount         = abs($record->amount);
                                //     $cost           = abs($record->cost);

                                //     $remaining = $amount - $cost;

                                //     $totalAmount = $totalAmount+$remaining;
                                //     $payment->remaining_amount = $totalAmount;
                                // }


                            }
                            $payment = $payment->toArray();
                            array_push($payments, $payment);
                        }
                    }
                }

                // Friends
                $friends = Friend::where('dog_id', $item->id)->orWhere('friend_id', $item->id)->get();
                $friend_ids = [];
                if(count($friends) > 0)
                {
                    foreach($friends as $friend)
                    {
                        $friend_id = ($friend->dog_id == $item->id) ? $friend->friend_id : $friend->dog_id;
                        $friendz = Dog::find($friend_id);
                        $friend->dog = $friendz;
                        array_push($friend_ids, $friend_id);
                    }
                }
                $item->friends = $friends;
            }
        }

        // return $payments;

        return view('admin.customer.preview', compact('customer' , 'dogs', 'payments'));
    }

    public function add_dog_view(Request $request, $customer_id)
    {
        if(!General::permissions('Kunde'))
        {
            return to_route('admin.settings');
        }

        $customers = Customer::get();
        $plans = Plan::get();
        $dogs = Dog::with('customer')->get();
        return view('admin.customer.add_dog', compact('customer_id', 'customers' , 'plans' , 'dogs'));
    }

    public function add_dog(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'picture' => 'nullable',
            'age' => 'nullable',
            'race' => 'nullable',
            'chip_number' => 'nullable',
            'health_problems' => 'nullable',
            'price_plan' => 'nullable',
            'daily_rate' => 'nullable',
            'allergy' => 'nullable',
        ]);

        $photo = 'no-user-picture.gif';

        if(isset($request->picture) && $request->picture != null)
        {
            $picture = $request->picture;
            $picture_name = time().$picture->getClientOriginalName();
            $picture->move(public_path('uploads/users/dogs'), $picture_name);
            $photo = $picture_name;
        }

        $is_medication = isset($request->is_medication) ? 1 : 0;
        $water_lover = isset($request->water_lover) ? 1 : 0;
        $is_allergy = isset($request->is_allergy) ? 1 : 0;
        $is_eating_habits = isset($request->is_eating_habits) ? 1 : 0;
        $is_special_eating = isset($request->is_special_eating) ? 1 : 0;


        $dog = Dog::create([
            'customer_id'=> $request->customer_id,
            'name'=> $request->name,
            'picture' => $photo,
            'age' => $request->age,
            'neutered' => (int)$request->neutered,
            'is_allergy' => $is_allergy,
            'allergy' => $request->allergy,
            'compatible_breed' => $request->race,
            'chip_number' => $request->chip_number,
            'is_medication' => $is_medication,
            'morgen' => $request->morgen,
            'mittag' => $request->mittag,
            'abend' => $request->abend,
            'is_eating_habits' => $is_eating_habits,
            'eating_morning' => $request->eating_morning,
            'eating_midday' => $request->eating_midday,
            'eating_evening' => $request->eating_evening,
            'health_problems' => $request->health_problems,
            'is_special_eating' => $is_special_eating,
            'special_morning' => $request->special_morning,
            'special_midday' => $request->special_midday,
            'special_evening' => $request->special_evening,
            'compatibility' => $request->compatibility,
            'water_lover' => $water_lover,
            'gender' => $request->gender,
            'weight' => $request->weight,
            'reg_plan' => $request->price_plan,
            'day_plan' => $request->daily_rate,
        ]);

        // Saving dog vists to db
        $visits = (isset($request->visits) && $request->visits != null) ? $request->visits : 0;
        $stay = (isset($request->days) && $request->days != null) ? $request->days : 0;

        Visit::create([
            'dog_id' => $dog->id,
            'visits' => $visits,
            'stay' => $stay
        ]);

        // Saving Pickups data to db
        $count = isset($request->pick_name) && is_array($request->pick_name) ? count($request->pick_name) : 0;
        if($count > 0)
        {
            for($i=0; $i < $count; $i++)
            {
                $name = $request->pick_name[$i];
                $phone = $request->pick_phone[$i];
                $id_number = isset($request->pick_id[$i]) ? $request->pick_id[$i] : null;
                $picture = isset($request->pick_file[$i]) ? $request->pick_file[$i] : false;

                $filename = null;

                if($picture)
                {
                    $picture_name = time().$picture->getClientOriginalName();
                    $picture->move(public_path('uploads/users/pickup'), $picture_name);
                    $filename = $picture_name;
                }

                Pickup::create([
                    'dog_id' => $dog->id,
                    'name' => $name,
                    'phone' => $phone,
                    'id_number'=> $id_number,
                    'picture'=> $filename,
                ]);
            }
        }

        //saving dog friends to db
        if(isset($request->dog_friends) && count($request->dog_friends) > 0)
        {
            foreach($request->dog_friends as $friend_id)
            {
                Friend::create([
                    'dog_id' => $dog->id,
                    'friend_id' => $friend_id
                ]);
            }
        }

        Session::flash('success' , 'Hund wurde erfolgreich hinzugefügt');
        return back();
    }

    public function edit_dog(Request $request, $id)
    {
        if(!General::permissions('Kunde'))
        {
            return to_route('admin.settings');
        }

        $customers = Customer::get();
        $plans = Plan::get();
        $dogs = Dog::with('customer')->get();

        $dog = Dog::with(['visit' , 'pickups', 'reg_plan_obj', 'customer'])->find($id);

        $dog->eating_habits = json_decode($dog->eating_habits);

        // get Dog Friends
        $friends = Friend::where('dog_id', $dog->id)->orWhere('friend_id', $dog->id)->get();
        $friend_ids = [];
        if(count($friends) > 0)
        {
            foreach($friends as $friend)
            {
                $friend_id = ($friend->dog_id == $dog->id) ? $friend->friend_id : $friend->dog_id;
                $friendz = Dog::find($friend_id);
                $friend->dog = $friendz;
                array_push($friend_ids, $friend_id);
            }
        }

        $dog->friend_ids = $friend_ids;
        $dog->friends = $friends;
        // $dog->friend_ids = $dog->friends->pluck('friend_id')->toArray();

        if($request->ajax())
        {
            return $dog;
        }

        return view('admin.customer.edit_dog', compact('customers' , 'plans' , 'dogs', 'dog'));
    }

    public function update_dog(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'age' => 'nullable',
            'race' => 'nullable',
            'chip_number' => 'nullable',
            'health_problems' => 'nullable',
            'price_plan' => 'required',
            'daily_rate' => 'required',
        ]);

        $is_medication = isset($request->is_medication) ? 1 : 0;
        $water_lover = isset($request->water_lover) ? 1 : 0;
        $is_allergy = isset($request->is_allergy) ? 1 : 0;
        $is_eating_habits = isset($request->is_eating_habits) ? 1 : 0;
        $is_special_eating = isset($request->is_special_eating) ? 1 : 0;

        //Updating Dog Info
        $dog = Dog::find($request->id);
        $dog->name = $request->name;
        $dog->age = $request->age;
        $dog->neutered = (int)$request->neutered;
        $dog->is_allergy = $is_allergy;
        $dog->allergy = $request->allergy;
        $dog->compatible_breed = $request->race;
        $dog->chip_number = $request->chip_number;
        $dog->is_medication = $is_medication;
        $dog->medication = $request->medication;
        $dog->health_problems = $request->health_problems;
        $dog->is_eating_habits = $request->is_eating_habits;
        $dog->morgen = $request->morgen;
        $dog->mittag = $request->mittag;
        $dog->abend = $request->abend;
        $dog->is_eating_habits = $is_eating_habits;
        $dog->eating_morning = $request->eating_morning;
        $dog->eating_midday = $request->eating_midday;
        $dog->eating_evening = $request->eating_evening;
        $dog->is_special_eating = $is_special_eating;
        $dog->special_morning = $request->special_morning;
        $dog->special_midday = $request->special_midday;
        $dog->special_evening = $request->special_evening;
        $dog->compatibility = $request->compatibility;
        $dog->water_lover = $water_lover;
        $dog->gender = $request->gender;
        $dog->weight = $request->weight;
        $dog->reg_plan = $request->price_plan;
        $dog->day_plan = $request->daily_rate;

        if(isset($request->picture) && $request->picture != null)
        {
            $picture = $request->picture;
            $picture_name = time().$picture->getClientOriginalName();
            $picture->move(public_path('uploads/users/dogs'), $picture_name);
            $dog->picture = $picture_name;
        }

        $dog->save();

        //Updating Visits info
        $visit = Visit::where('dog_id', $request->id)->first();
        if($visit)
        {
            $visit->visits = $request->visits;
            $visit->stay = $request->days;
            $visit->save();
        }

        Session::flash('success', 'Die Hundeinformationen wurden erfolgreich aktualisiert');
        return to_route('admin.customers.preview' , ['id' => $dog->customer_id]);
    }

    public function update_dog_adoption(Request $request)
    {
        $dog = Dog::find($request->id);
        $dog->adopt_date = $request->adoption_date;
        $dog->status = 2;
        $dog->save();

        Session::flash('success','Erfolgreich geupdated');
        return back();
    }

    public function update_dog_death(Request $request)
    {
        $dog = Dog::find($request->id);
        $dog->died = $request->date_of_death;
        $dog->status = 3;
        $dog->save();

        Session::flash('success','Erfolgreich geupdated');
        return back();
    }

    public function delete_dog(Request $request)
    {
        $reservations = Reservation::where('dog_id', $request->id)->get();
        foreach($reservations as $obj)
        {
            $obj->delete();
        }

        Dog::where('id', $request->id)->delete();

        Session::flash('error','Hund wurde erfolgreich gelöscht');
        return back();
    }

    public function add_pickups(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        for($i=0;$i<count($request->pick_name);$i++)
        {
            $pickup = new Pickup();
            $pickup->dog_id = $request->id;
            $pickup->name = $request->pick_name[$i];
            $pickup->phone = $request->pick_phone[$i];
            $pickup->id_number = $request->pick_id[$i];
            if(isset($request->pick_file[$i] ) && $request->pick_file[$i] != null)
            {
                $picture = $request->pick_file[$i];
                $picture_name = time().$picture->getClientOriginalName();
                $picture->move(public_path('uploads/users/pickup'), $picture_name);
                $pickup->picture = $picture_name;
            }

            $pickup->save();
        }

        Session::flash('success', 'Die Pickups wurden erfolgreich hinzugefügt');
        return back();
    }

    public function delete_pickup(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        Pickup::where('id', $request->id)->delete();

        Session::flash('error', 'Pickup wurde erfolgreich gelöscht');
        return back();
    }

    public function update_pickup(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'phone' => 'required',
        ]);

        $pickup = Pickup::find($request->id);
        $pickup->name = $request->name;
        $pickup->phone = $request->phone;
        $pickup->id_number = $request->id_number;
        if(isset($request->file) && $request->file != null)
        {
            $picture = $request->file;
            $picture_name = time().$picture->getClientOriginalName();
            $picture->move(public_path('uploads/users/pickup'), $picture_name);
            $pickup->picture = $picture_name;
        }
        $pickup->save();

         Session::flash('success', 'Pickup wurde erfolgreich aktualisiert');
         return back();
    }

    public function remove_friend(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $friend = Friend::find($request->id);
        if($friend)
        {
            $friend->delete();
        }

        if($request->ajax())
        {
            return true;
        }

        Session::flash('success', 'Freund wurde erfolgreich entfernt');
        return back();
    }

    public function add_friend(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'dog_friends' => 'required',
        ]);

        foreach($request->dog_friends as $friend_id)
        {
            $friend = Friend::where('dog_id', $request->id)->where('friend_id', $friend_id)->first();
            if(!$friend)
            {
                Friend::create([
                    'dog_id' => $request->id,
                    'friend_id' => $friend_id
                ]);
            }
        }

        Session::flash('success', 'Freunde wurden erfolgreich hinzugefügt');
        return back();
    }

    public function v_v_view()
    {
        if(!General::permissions('Verstorbene Hunde'))
        {
            return to_route('admin.settings');
        }

        $dogsVermittelt = Dog::with('customer')->where('status',2)->orderBy('id' , 'desc')->get();
        $dogsVerstorben = Dog::with('customer')->where('status',3)->orderBy('id' , 'desc')->get();
        return view("admin.vandv.index" , compact("dogsVermittelt", "dogsVerstorben"));
    }

    public function v_v_dieddog(Request $request)
    {
        Dog::where("id", $request->id)->delete();

        Session::flash("error","Der Datensatz wurde erfolgreich gelöscht");
        return back();
    }

    public function v_v_adopteddog(Request $request)
    {
        Dog::where("id", $request->id)->delete();

        Session::flash("error","Der Datensatz wurde erfolgreich gelöscht");
        return back();
    }

    public function dog_ranks_view(Request $request)
    {
        if(!General::permissions('Hunde bleiben'))
        {
            return to_route('admin.settings');
        }

        $year = (isset($_GET['year'])) ? $_GET['year'] : date('Y');

        // Ajax Here
        if($request->ajax())
        {
            $keyword = $request->keyword;
            $date = $request->date;

            $dogs = Reservation::selectRaw('MAX(reservations.id) as id, reservations.dog_id, SUM(DATEDIFF(date(`checkout_date`), date(`checkin_date`)) + 1) AS total_stay_days')
            ->with('dog.customer')
            ->join('dogs', 'reservations.dog_id', '=', 'dogs.id')
            ->whereYear('reservations.checkin_date', $date)
            ->where('dogs.name','like','%'.$keyword.'%')
            ->where(function ($query) {
                $query->where('reservations.status', 1)
                    ->orWhere('reservations.status', 2);
            })
            ->groupBy('dog_id')
            ->orderBy('total_stay_days', 'DESC')
            ->get();

            if(count($dogs) > 0)
            {
                foreach($dogs as $dog)
                {
                    $cost = 0.00;
                    $process = DB::select("SELECT sum(`p`.`received_amount`) as `payment` FROM `reservations` as `dr` ,`payments` as `p` WHERE `p`.`res_id`=`dr`.`id` and `dr`.`dog_id`=$dog->dog_id");
                    if(count($process) > 0)
                    {
                        $cost = ($process[0]->payment) ? abs($process[0]->payment) : 0.00;
                    }
                    $dog->cost = $cost;
                }
            }

            return $dogs;
        }

        $dogs = Reservation::selectRaw('MAX(id) as id, dog_id, SUM(DATEDIFF(date(`checkout_date`), date(`checkin_date`)) + 1) AS total_stay_days')
        ->with('dog.customer')
        ->whereYear('checkin_date', $year)
        ->where(function ($query) {
            $query->where('status', 1)
                  ->orWhere('status', 2);
        })
        ->groupBy('dog_id')
        ->orderBy('total_stay_days', 'DESC')
        ->paginate(20);

        if(count($dogs) > 0)
        {
            foreach($dogs as $dog)
            {
                $cost = 0.00;
                $process = DB::select("SELECT sum(`p`.`received_amount`) as `payment` FROM `reservations` as `dr` ,`payments` as `p` WHERE `p`.`res_id`=`dr`.`id` and `dr`.`dog_id`=$dog->dog_id");
                if(count($process) > 0)
                {
                    $cost = ($process[0]->payment) ? abs($process[0]->payment) : 0.00;
                }
                $dog->cost = $cost;
            }
        }

        return view('admin.ranking.index', compact('dogs'));
    }

    public function dogs_calendar_view(Request $request)
    {
        return view('admin.calendar.dogs');
    }

    public function update_note(Request $request)
    {
        $dog = Dog::find($request->id);
        if($dog)
        {
            $dog->note = $request->note;
            $dog->save();
        }
        return true;
    }
}
