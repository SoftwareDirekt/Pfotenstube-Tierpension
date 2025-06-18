<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;
use App\Models\Payment;
use Notification;
use Str;
use Session;
use Auth;
use Log;
use DB;
use Carbon\Carbon;

class UsersController extends Controller
{
    public function login_view()
    {
        return view('user.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember_me = false;
        $credentials = $request->only('email', 'password');

        if(isset($request->remember_me))
        {
            $remember_me = true;
        }

        if(Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ], $remember_me))
        {
            return to_route('user.dashboard');
        }
        
        \Session::flash('error', 'Invalid email address or password');
        return back();

    }

    public function recover_account()
    {
        return view('user.forget_password');
    }

    public function forgot_account(Request $request)
    {
        $request->validate([
            'email' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if(!$user)
        {
            Session::flash('error', 'Provided email address is not associated with any account');
            return back();
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );
        
        if($status === Password::RESET_LINK_SENT)
        {
            Session::flash('success', 'We have sent account recovery instructions to your email');
            return back();
        }
        else{
            Session::flash('error', 'Error! something went wrong.');
            return back();
        }

    }

    public function reset_password_view(Request $request)
    {
        return view('user.reset-password', ['token' => $request->token, 'email' => $request->email]);
    }

    public function reset_password(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|max:25|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->setRememberToken(Str::random(60));
     
                $user->save();
     
                event(new PasswordReset($user));
            }
        );

        if($status === Password::PASSWORD_RESET)
        {
            Session::flash('success', 'Account password successfully updated');
            return redirect()->route('user.login.view');
        }
        else{
            Session::flash('error', 'Error! something went wrong.');
            return back();
        }
    }

    public function logout()
    {
        Auth::logout();
        return to_route('user.login.view');
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        return view('user.dashboard');
    }

    public function testing()
    {
        $password = '12345678';
        $pass = bcrypt($password);
        return $pass;
    }

    public function employee_track()
    {
        $payments = Payment::with(['reservation' => function($query){
            $query->with(['dog' => function ($qry){
                $qry->with('customer');
            }]);
        }])->orderBy('id','desc')->paginate(30);

        return view('admin.workingtimemeasurement.index', compact('payments'));
    }

    // Database dumps
    public function dump_customers()
    {
        $json = file_get_contents('dump/customers.json');
        $payload = json_decode($json);
        $customers = $payload[2]->data;

        foreach($customers as $obj)
        {
            \App\Models\Customer::create([
                'id' => $obj->id,
                'type' => $obj->type,
                'title' => $obj->title,
                'name' => $obj->name,
                'email' => $obj->email,
                'email_verified_at' => $obj->created,
                'street' => $obj->address,
                'city' => $obj->city,
                'country' => $obj->land,
                'zipcode' => $obj->zip,
                'phone' => $obj->phone_number,
                'emergency_contact' => $obj->e_phone,
                'veterinarian' => $obj->d_phone,
                'id_number' => $obj->id_number,
                'picture' => $obj->picture,
                'created_at' => $obj->created,
                'updated_at' => $obj->created,
            ]);
        }

        return 'Customers Added';
    }

    public function dump_dogs()
    {
        $json = file_get_contents('dump/dogs.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            $is_medication = (!$obj->is_medication) ? 0 : 1;
            $water_lover = (!$obj->water_lover) ? 0 : 1;

            \App\Models\Dog::create([
                'id' => $obj->id,
                'customer_id' => $obj->cid,
                'name' => $obj->name,
                'picture' => $obj->picture,
                'age' => $obj->age,
                'neutered' => $obj->neutered,
                'compatible_breed' => $obj->compatible_breed,
                'chip_number' => $obj->chip_number,
                'is_medication' => $is_medication,
                'medication' => $obj->medication,
                'health_problems' => $obj->health_problems,
                'eating_habits' => $obj->eating_habits,
                'compatibility' => $obj->compatibility,
                'water_lover' => $water_lover,
                'status' => $obj->status,
                'died' => $obj->died,
                'gender' => $obj->gender,
                'weight' => $obj->weight,
                'note' => $obj->note,
                'reg_plan' => $obj->reg_plan,
                'day_plan' => $obj->day_plan,
                'created_at' => $obj->created,
                'updated_at' => $obj->created,
            ]);
        }

        return 'Dogs Added';
    }

    public function dump_reservations()
    {
        $json = file_get_contents('dump/reservations.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Reservation::create([
                'id' => $obj->id,
                'dog_id' => $obj->dog_id,
                'room_id' => $obj->room_id,
                'checkin_date' => $obj->checkin,
                'checkout_date' => $obj->checkout,
                'plan_id' => $obj->plan_id,
                'status' => $obj->status,
                'created_at' => $obj->created,
                'updated_at' => $obj->updated,
            ]);
        }

        return 'Reservations Added';
    }

    public function dump_visits()
    {
        $json = file_get_contents('dump/visits.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Visit::create([
                'id' => $obj->id,
                'dog_id' => $obj->dog_id,
                'visits' => $obj->visits,
                'stay' => $obj->stay,
                'created_at' => $obj->created,
                'updated_at' => $obj->created,
            ]);
        }

        return 'Visits Added';
    }

    public function dump_events()
    {
        $json = file_get_contents('dump/events.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Event::create([
                'id' => $obj->id,
                'title' => $obj->title,
                'start' => $obj->start,
                'end' => $obj->end,
                'uid' => $obj->uid,
                'status' => $obj->status,
                'backgroundColor' => $obj->backgroundColor,
                'textColor' => $obj->textColor,
                'created_at' => $obj->created,
                'updated_at' => $obj->created,
            ]);
        }

        return 'Events Added';
    }

    public function dump_payments()
    {
        $json = file_get_contents('dump/payments.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Payment::create([
                'id' => $obj->id,
                'res_id' => $obj->res_id,
                'type' => $obj->type,
                'cost' => $obj->cost,
                'received_amount' => $obj->amount,
                'discount' => $obj->discount,
                'discount_amount' => $obj->discountAmount,
                'status' => $obj->status,
                'created_at' => $obj->created,
                'updated_at' => $obj->updated,
            ]);
        }

        return 'Payments Added';
    }

    public function dump_pickups()
    {
        $json = file_get_contents('dump/pickups.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Pickup::create([
                'id' => $obj->id,
                'dog_id' => $obj->dog_id,
                'name' => $obj->name,
                'phone' => $obj->phone_number,
                'id_number' => $obj->id_number,
                'picture' => $obj->picture,
                'created_at' => $obj->created,
                'updated_at' => $obj->created,
            ]);
        }

        return 'Pickups Added';
    }

    public function dump_rooms()
    {
        $json = file_get_contents('dump/rooms.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Room::create([
                'id' => $obj->id,
                'number' => $obj->number,
                'type' => $obj->type,
                'capacity' => $obj->capacity,
                'order' => $obj->order,
                'status' => $obj->status,
                'created_at' => $obj->created,
                'updated_at' => $obj->updated,
            ]);
        }

        return 'Rooms Added';
    }

    public function dump_tasks()
    {
        $json = file_get_contents('dump/tasks.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Task::create([
                'id' => $obj->id,
                'title' => $obj->title,
                'created_at' => $obj->date,
                'updated_at' => $obj->date,
            ]);
        }

        return 'Tasks Added';
    }

    public function dump_todos()
    {
        $json = file_get_contents('dump/todos.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Todo::create([
                'id' => $obj->id,
                'user_id' => $obj->uid,
                'task' => $obj->task,
                'status' => $obj->status,
                'created_at' => $obj->created,
                'updated_at' => $obj->completed,
            ]);
        }

        return 'Todos Added';
    }

    public function dump_users()
    {
        $json = file_get_contents('dump/users.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        $now = \Carbon\Carbon::now()->toDateTimeString();

        foreach($records as $obj)
        {
            \App\Models\User::create([
                'id' => $obj->id,
                'name' => $obj->name,
                'email' => $obj->email,
                'email_verified_at' => $now,
                'phone' => $obj->phone,
                'username' => $obj->username,
                'department' => $obj->department,
                'password' => $obj->password,
                'type' => $obj->type,
                'address' => $obj->address,
                'city' => $obj->city,
                'country' => $obj->country,
                'status' => $obj->status,
                'picture' => $obj->picture,
                'role' => 2,
                'created_at' => $obj->created,
                'updated_at' => $obj->timestamp,
            ]);
        }

        return 'Employees Added';
    }

    public function dump_plans()
    {
        $json = file_get_contents('dump/plans.json');
        $payload = json_decode($json);
        $records = $payload[2]->data;

        foreach($records as $obj)
        {
            \App\Models\Plan::create([
                'id' => $obj->id,
                'title' => $obj->title,
                'type' => $obj->type,
                'price' => $obj->price,
                'discount' => $obj->discount,
                'flat_rate' => $obj->flatRate,
                'created_at' => $obj->created,
                'updated_at' => $obj->updated,
            ]);
        }

        return 'Plans Added';

    }

}
