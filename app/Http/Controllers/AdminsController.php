<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Helpers\General;
use App\Models\Admin;
use App\Models\User;
use App\Models\Dog;
use App\Models\Task;
use App\Models\Room;
use App\Models\Todo;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Plan;
use App\Models\Payment;
use Carbon\Carbon;
use Log;

class AdminsController extends Controller
{
    public function login_view()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->remember))
        {
            return to_route('admin.dashboard');
        }

        Session::flash('error', 'Invalid email address or password');
        return back();
    }
    ///pin functionality

    public function validatePin(Request $request)
    {
        // Use config() instead of env() to work with cached config in production
        $correctPin = config('app.admin_pin');

        if ($request->pin === $correctPin) {
            Session::put('lock', true);
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function logoutSession()
    {
        Session::forget('lock');

        return response()->json(['success' => true]);
    }


    public function dashboard()
    {
        if(!General::permissions('Armaturenbrett'))
        {
            return to_route('admin.settings');
        }
        $currentMonth = Carbon::now()->month;
        $reservations = Reservation::with('plan', 'dog.customer')
            ->whereHas('dog')
            ->where('status', 3)
            ->orderBy('checkin_date')
            ->get();

        $today = Carbon::now()->toDateString();
        $customerBalanceCache = [];

        foreach($reservations as $obj)
        {
            // check for checkin date
            $checkin_check = $obj->checkin_date;
            $checkout_check = $obj->checkout_date;
            $checkin_check_date = Carbon::parse($checkin_check)->toDateString();
            $checkout_check_date = Carbon::parse($checkout_check)->toDateString();

            $dog_id = $obj->dog_id;

            if(isset($obj->dog->customer))
            {
                $customer_id = $obj->dog->customer->id;
                $obj->totalAmount = $this->calculateCustomerBalance($customer_id, $customerBalanceCache);
            }

            // check interval
            $now = Carbon::now();
            $checkout_date = Carbon::parse($obj->checkout_date);
            $interval = $now->diff($checkout_date);
            $color = 'normal';
            if ($interval->days == 0) {
                $color = 'danger';
            }
            if ($interval->invert == 1) {
                $color = 'danger';
            }
            $obj->color = $color;

            $currentYear = Carbon::now()->year;

            // Get visit counts from Visit model (includes initial values + increments)
            // All dogs should have a Visit record (created on dog creation or by legacy script)
            $visit = \App\Models\Visit::where('dog_id', $dog_id)->first();
            $visits = $visit ? ($visit->visits ?? 0) : 0;
            $days = $visit ? ($visit->stay ?? 0) : 0;
            $obj->stays = "$visits/$days";
        }

        // Get Dogs in Rooms
        $rooms = Room::where('status', 1)->orderBy('order','asc')->get();
        $total_room_occupacy = 0;
        $total_out = 0;
        $total_orgs = 0;
        $fetched_dogs = [];

        foreach($rooms as $room)
        {
            $reservations_data = Reservation::with('plan', 'dog.customer')
            ->selectRaw('MAX(id) as id, dog_id, MAX(room_id) as room_id, MAX(checkin_date) as checkin_date, MAX(checkout_date) as checkout_date, MAX(plan_id) as plan_id, MAX(status) as status, MAX(created_at) as created_at')
            ->whereHas('dog')
            ->where('room_id', $room->id)
            ->where('status', 1)
            ->groupBy('dog_id')
            ->orderBy('id','desc')
            ->get();

            $room->reservations = $reservations_data;
            $total_room_occupacy += count($reservations_data);

            if(count($room->reservations) > 0)
            {
                foreach($room->reservations as $key => $res)
                {
                    if(!isset($res->dog))
                    {
                        continue;
                    }
                    if(in_array($res->dog_id, $fetched_dogs))
                    {
                        $room->reservations->forget($key);
                        continue;
                    }

                    array_push($fetched_dogs, $res->dog_id);

                    if(isset($res->dog->customer))
                    {
                        $customer_id = $res->dog->customer->id;
                        $res->totalAmount = $this->calculateCustomerBalance($customer_id, $customerBalanceCache);
                    }


                    $now = Carbon::now();
                    $checkout_date = Carbon::parse($res->checkout_date)->endOfDay();
                    $interval = $now->diff($checkout_date);
                    $color = 'normal';

                    if ($res->dog->customer->type == 'Organisation') {
                        $total_orgs += 1;
                        $color = 'primary';
                    }

                    if ($interval->days == 0) {
                        $color = 'danger';
                        $total_out = $total_out + 1;
                    }
                    elseif ($interval->invert == 1) {
                        $color = 'danger';
                        $total_out = $total_out + 1;
                    }

                    $res->color = $color;

                    // Stays Counter
                    $dog_id = $res->dog_id;
                    $currentYear = Carbon::now()->year;

                    // Get visit counts from Visit model (includes initial values + increments)
                    // All dogs should have a Visit record (created on dog creation or by legacy script)
                    $visit = \App\Models\Visit::where('dog_id', $dog_id)->first();
                    $visits = $visit ? ($visit->visits ?? 0) : 0;
                    $days = $visit ? ($visit->stay ?? 0) : 0;
                    $res->stays = "$visits/$days";
                }
            }
        }

        $plans = Plan::orderBy('id', 'asc')->get();
        $dogs = Dog::with('customer')->get();
        $tasks = Task::orderBy('created_at','desc')->get();
        $users = User::orderBy('created_at','desc')->get();

        $total_reservations = count($reservations);


        return view('admin.dashboard', compact('currentMonth','reservations' , 'dogs', 'tasks','rooms', 'plans', 'total_reservations', 'total_room_occupacy', 'total_out', 'total_orgs', 'users'));

    }

    private function calculateCustomerBalance(int $customerId, array &$cache): float
    {
        if (array_key_exists($customerId, $cache)) {
            return $cache[$customerId];
        }

        // Use customer balance column directly (accounts for settlements and wallet)
        $customer = Customer::find($customerId);
        $balance = $customer ? (float)($customer->balance ?? 0) : 0;

        $cache[$customerId] = $balance;

        return $cache[$customerId];
    }

    public function admin_settings()
    {
        return view('admin.auth.settings');
    }

    public function admin_settings_post(Request $request)
    {
        $request->validate([
            'currentPassword' => 'required|min:8|max:100',
            'newPassword' => [
                'required',
                'different:currentPassword',
                'string',
                'min:5',             // must be at least 8 characters in length
                // 'regex:/[A-Z]/',      // must contain at least one uppercase letter
                // 'regex:/[0-9]/',      // must contain at least one digit
            ],
            'confirmPassword' => 'required|same:newPassword',
        ]);

        // $admin = Auth::guard('admin')->user();
        $admin = Auth::user();

        if (!password_verify($request->currentPassword, $admin->password))
        {
            return redirect()->back()->withErrors([Session::flash('error' , 'Das aktuelle Passwort ist falsch.')])->withInput();
        }

        // Hashing the new password
        $newPassword = $request->newPassword;
        $hashedPassword = bcrypt($newPassword);

        // Update the admin's password
        $admin->password = $hashedPassword;
        $admin->save();

        return redirect()->back()->withErrors([Session::flash('success' , 'Passwort erfolgreich aktualisiert.')])->withInput();
    }
    public function admin_logout(Request $request)
    {
        Auth::logout();

        Session::flash('success' , 'Du hast dich erfolgreich abgemeldet.');
        return redirect('/admin/login');
    }
    public function employees(Request $request)
    {
        if(!General::permissions('Mitarbeiter'))
        {
            return to_route('admin.settings');
        }

        if($request->ajax())
        {
            $keyword = isset($request->keyword) ? $request->keyword :"";
            $order = isset($request->order) ? $request->order : 'asc';

            $where = [];
            if(isset($request->keyword) && $request->keyword != '')
            {
                $where = function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->keyword . '%')
                        ->orWhere('email', 'like', '%'. $request->keyword. '%')
                        ->orWhere('phone', 'like', '%'. $request->keyword. '%');
                };
            }

            $users = User::where($where)
            ->where('role', 2)
            ->orderBy('id', $order)->get();
            return $users;
        }

        $users = User::where('role', 2)->orderBy('id','desc')->paginate(20);
        return view('admin.employees', compact('users'));
    }

    public function add_employees(Request $request)
    {
        if(!General::permissions('Mitarbeiter'))
        {
            return to_route('admin.settings');
        }

        $pages = Page::all();
        return view('admin.employees-add',compact('pages'));
    }

    public function post_employees(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'department' => 'nullable',
            'password' => 'required',
        ]);

        $ex = explode("@", $request->email);
        $username = $ex[0];

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->username = $username;
        $user->department = $request->department;
        $user->password = bcrypt($request->password);
        $user->address = $request->address;
        $user->city = $request->city;
        $user->country = $request->country;
        $user->phone = $request->phone;
        $user->permissions = isset($request->permissions) && !empty($request->permissions) ? json_encode($request->permissions) : null;

        if(isset($request->picture) && $request->picture != null)
        {
            try {
                $picture = $request->picture;
                $originalName = $picture->getClientOriginalName();
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $picture_name = uniqid() . '_' . $sanitizedName;
                
                $uploadPath = public_path('uploads/users');
                if (!file_exists($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                    }
                }
                
                $picture->move($uploadPath, $picture_name);
                $user->picture = $picture_name;
            } catch (\Exception $e) {
                \Log::error('User picture upload failed: ' . $e->getMessage());
                Session::flash('error', 'Fehler beim Hochladen des Benutzerbildes: ' . $e->getMessage());
                return back();
            }
        }

        $user->save();

        Session::flash('success', 'Benutzer erfolgreich hinzugefügt');
        return back();

    }

    public function edit_employees($id)
    {
        if(!General::permissions('Mitarbeiter'))
        {
            return to_route('admin.settings');
        }

        $user = User::where('id', $id)->first();
        $pages = Page::all();
        return view('admin.employees-edit', compact(['user','pages']));
    }
    public function update_employees(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $user = User::find($request->id);
        $user->name = $request->name;
        $user->department = $request->department;
        $user->address = $request->address;
        $user->city = $request->city;
        $user->country = $request->country;
        $user->phone = $request->phone;
        $user->permissions = isset($request->permissions) && !empty($request->permissions) ? json_encode($request->permissions) : null;

        if($request->password != null)
        {
            $user->password = bcrypt($request->password);
        }

        if(isset($request->picture) && $request->picture != null)
        {
            try {
                $picture = $request->picture;
                $originalName = $picture->getClientOriginalName();
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $picture_name = uniqid() . '_' . $sanitizedName;
                
                $uploadPath = public_path('uploads/users');
                if (!file_exists($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                    }
                }
                
                $picture->move($uploadPath, $picture_name);
                $user->picture = $picture_name;
            } catch (\Exception $e) {
                \Log::error('User picture upload failed: ' . $e->getMessage());
                Session::flash('error', 'Fehler beim Hochladen des Benutzerbildes: ' . $e->getMessage());
                return back();
            }
        }

        $user->save();

        Session::flash('success', 'Benutzer erfolgreich aktualisiert');
        return to_route('admin.employees');
    }

    public function delete_employees(Request $request)
    {

        $user = User::where('id', $request->id)->first();
        if($user)
        {
            $user->delete();
        }

        Session::flash('error', 'Benutzer erfolgreich gelöscht');
        return to_route('admin.employees');
    }

    public function createEvent(Request $request)
    {
        $uId = $request->id;
        $today = now()->toDateString();

        // $existingEvent = Event::where('uid', $uId)
        //                     ->whereDate('start', $today)
        //                     ->first();

        // if ($existingEvent) {
        //     return response()->json(['success' => false, 'message' => 'Die Anwesenheit für heute wurde bereits erfasst.']);
        // }

        $user = User::find($uId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Benutzer nicht gefunden.'
            ]);
        }

        $randomColor = $this->getRandomColorFromList();
        $event = Event::create([
            'start' => now(),
            'uid' => $uId,
            'status' => 'Arbeit',
            'backgroundColor' => $randomColor['backgroundColor'],
            'textColor' => $randomColor['color'],
        ]);

        if ($event) {
            return response()->json([
                'success' => true,
                'message' => 'Anwesenheit gestartet. Du bist jetzt eingecheckt.',
                'user' => [
                    'name' => $user->name,
                    'picture' => $user->picture ? asset('uploads/users/' . $user->picture) : null
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Ereignis konnte nicht erstellt werden.']);
    }

    public function endEvent(Request $request)
    {
        $event = Event::where('uid', $request->id)->whereNull('end')->first();

        if ($event) {
            $event->end = now();
            $event->save();
            return response()->json(['success' => true, 'message' => 'Anwesenheit erfolgreich beendet. Du wurdest ausgecheckt.']);
        }

        return response()->json(['success' => false, 'message' => 'Es wurde kein aktives Ereignis zum Ende gefunden.']);
    }

    public function checkEventStatus()
    {
        $statuses = Event::select('uid', \DB::raw('CASE
            WHEN end IS NULL THEN "ongoing"
            WHEN DATE(start) = CURDATE() THEN "completed"
            ELSE "none" END as status'))
            ->get();

        $statuses = $statuses->map(function($eventStatus) {
            $user = User::find($eventStatus->uid);
            $eventStatus->user = [
                'name' => $user->name,
                'picture' => $user->picture ? asset('uploads/users/' . $user->picture) : null,
            ];
            return $eventStatus;
        });

        return response()->json($statuses);
    }

    private function getRandomColorFromList()
    {
        $colorCodes = [
            ['backgroundColor' => '#FF0000', 'color' => '#FFFFFF'],
            ['backgroundColor' => '#00FF00', 'color' => '#000000'],
            // Add all other colors here
        ];
        return $colorCodes[array_rand($colorCodes)];
    }

    //plan room
    public function updateRoomCondition(Request $request)
    {
        $room = Room::find($request->room_id);

        if (!$room) {
            return response()->json(['status' => 'error', 'message' => 'Room not found.'], 404);
        }

        $room->room_condition = $request->room_condition;
        $room->save();

        return response()->json(['status' => 'success', 'room_condition' => $room->room_condition], 200);
    }



}
