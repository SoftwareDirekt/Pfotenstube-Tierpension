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
use App\Models\Vaccination;
use App\Models\DogDocument;
use App\Helpers\General;
use App\Services\HelloCashService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use DB;

class CustomersController extends Controller
{
    protected $hellocashService;

    public function __construct(HelloCashService $hellocashService)
    {
        $this->hellocashService = $hellocashService;
    }

    public function customers(Request $request)
    {
        if(!General::permissions('Kunde'))
        {
            return to_route('admin.settings');
        }

        // Validate and sanitize input parameters
        $order = in_array(strtolower($request->input('order', 'desc')), ['asc', 'desc']) 
            ? strtolower($request->input('order', 'desc')) 
            : 'desc';
        
        $allowedSortFields = ['id_number', 'name', 'created_at', 'email', 'phone'];
        $sortBy = in_array($request->input('sort_by', 'id_number'), $allowedSortFields)
            ? $request->input('sort_by', 'id_number')
            : 'id_number';

        if($request->ajax())
        {
            $keyword = isset($request->keyword) ? $request->keyword :"";

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

            // Apply natural sorting for id_number (handles K1, KW1, 1, 100, etc.)
            if ($sortBy === 'id_number') {
                // Extract numeric part: for "K1" or "KW1" get "1", for "100" get "100"
                $customers = $customersQuery
                    ->orderByRaw("CASE 
                        WHEN id_number IS NULL OR id_number = '' THEN 1 
                        ELSE 0 
                    END")
                    ->orderByRaw("CAST(
                        CASE 
                            WHEN id_number REGEXP '^[0-9]+$' 
                            THEN id_number
                            WHEN id_number REGEXP '^[A-Za-z]+[0-9]+$' 
                            THEN REGEXP_REPLACE(id_number, '^[A-Za-z]+', '')
                            ELSE '0'
                        END 
                    AS UNSIGNED) " . strtoupper($order))
                    ->orderBy('id_number', $order)
                    ->limit(30)
                    ->get();
            } else {
                $customers = $customersQuery->orderBy($sortBy, $order)->limit(30)->get();
            }

            return $customers;
        }

        $customersQuery = Customer::with(['dogs' => function($query){
            $query->where('status', 1);
            $query->with('reg_plan_obj','day_plan_obj');
        }]);

        // Apply natural sorting for id_number (handles K1, KW1, 1, 100, etc.)
        if ($sortBy === 'id_number') {
            // Extract numeric part: for "K1" or "KW1" get "1", for "100" get "100"
            $customersQuery->orderByRaw("CASE 
                WHEN id_number IS NULL OR id_number = '' THEN 1 
                ELSE 0 
            END")
            ->orderByRaw("CAST(
                CASE 
                    WHEN id_number REGEXP '^[0-9]+$' 
                    THEN id_number
                    WHEN id_number REGEXP '^[A-Za-z]+[0-9]+$' 
                    THEN REGEXP_REPLACE(id_number, '^[A-Za-z]+', '')
                    ELSE '0'
                END 
            AS UNSIGNED) " . strtoupper($order))
            ->orderBy('id_number', $order);
        } else {
            $customersQuery->orderBy($sortBy, $order);
        }

        $customers = $customersQuery->paginate(30);

        // Preserve all query parameters in pagination links
        $customers->appends($request->query());

        return view('admin.customer.list', compact('customers', 'order', 'sortBy'));
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:Stammkunde,Organisation',
            'email' => 'nullable|email|unique:customers|max:255',
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|unique:customers|max:255',
            'title' => 'nullable|string|max:50',
            'profession' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:50',
            'veterinarian' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Der Name ist erforderlich.',
            'name.string' => 'Der Name muss ein Text sein.',
            'type.required' => 'Der Typ ist erforderlich.',
            'type.in' => 'Der Typ muss entweder Stammkunde oder Organisation sein.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'id_number.unique' => 'Diese ID-Nummer wird bereits verwendet.',
        ]);

        $photo = 'no-user-picture.gif';

        if(isset($request->picture) && $request->picture != null)
        {
            $uploadResult = $this->handleFileUpload($request->picture, '');
            if (!$uploadResult['success']) {
                Session::flash('error', 'Fehler beim Hochladen des Kundenbildes: ' . $uploadResult['error']);
                return back();
            }
            $photo = $uploadResult['filename'];
        }

        try {
            DB::beginTransaction();
            
            // Create customer
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

            // Sync customer to HelloCash
            $result = $this->hellocashService->createUser($customer);
            
            if (!$result['success'] || empty($result['user_id'])) {
                // HelloCash sync failed - rollback transaction
                DB::rollBack();
                $errorMessage = $result['error'] ?? 'Unbekannter Fehler bei der Registrierkasse-Synchronisation';
                Log::error('HelloCash sync failed during customer creation', [
                    'customer_name' => $request->name ?? 'Unknown',
                    'error' => $errorMessage,
                ]);
                Session::flash('error', 'Der Kunde konnte nicht erstellt werden: ' . $errorMessage);
                return redirect()->back()->withInput($request->except('picture', 'dogs'));
            }

            // Set HelloCash ID
            $customer->hellocash_customer_id = (int)$result['user_id'];
            $customer->save();

            // Commit transaction
            DB::commit();
            
        } catch (\Exception $e) {
            // Exception occurred - rollback transaction if transaction was started
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Exception while creating customer with HelloCash sync', [
                'customer_name' => $request->name ?? 'Unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Session::flash('error', 'Fehler beim Erstellen des Kunden. Bitte versuchen Sie es erneut.');
            return redirect()->back()->withInput($request->except('picture', 'dogs'));
        }

        // Second Transaction: Dog Creation
        if(isset($request->dogs) && is_array($request->dogs) && count($request->dogs) > 0)
        {
            try {
                DB::beginTransaction();
                
                foreach($request->dogs as $dog)
                {
                    $photo = 'no-user-picture.gif';

                    if(isset($dog['picture']) && $dog['picture'] != null)
                    {
                        $uploadResult = $this->handleFileUpload($dog['picture'], 'dogs');
                        if (!$uploadResult['success']) {
                            Session::flash('error', 'Fehler beim Hochladen des Hundebildes: ' . $uploadResult['error']);
                            return back();
                        }
                        $photo = $uploadResult['filename'];
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
                        'weight' => $dog['weight'],
                        'reg_plan' => $dog['price_plan'],
                        'day_plan' => $dog['daily_rate'],
                    ]);

                    // Saving dog visits to db (initial values)
                    $visitCounter = new \App\Services\VisitCounterService();
                    $visits = (isset($dog['visits']) && $dog['visits'] != null) ? (int)$dog['visits'] : 0;
                    $stay = (isset($dog['days']) && $dog['days'] != null) ? (int)$dog['days'] : 0;
                    $visitCounter->setInitialCounts($dog_db->id, $visits, $stay);

                    // Save Pickups
                    if(isset($dog['picks']) && count($dog['picks']) > 0)
                    {
                        foreach($dog['picks'] as $pick)
                        {
                            $filename = null;
                            $picture = isset($pick['file']) ? $pick['file'] : false;
                            if($picture)
                            {
                                $uploadResult = $this->handleFileUpload($picture, 'pickup');
                                if ($uploadResult['success']) {
                                    $filename = $uploadResult['filename'];
                                } else {
                                    // Continue without picture rather than failing entire operation
                                    Log::warning('Pickup picture upload failed, continuing without picture: ' . $uploadResult['error']);
                                    $filename = null;
                                }
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

                    // Save Vaccinations
                    if(isset($dog['vaccinations']) && count($dog['vaccinations']) > 0)
                    {
                        foreach($dog['vaccinations'] as $vaccination)
                        {
                            if(
                                empty($vaccination['vaccine_name'] ?? null) ||
                                empty($vaccination['vaccination_date'] ?? null) ||
                                empty($vaccination['next_vaccination_date'] ?? null)
                            ) {
                                continue;
                            }

                            $is_vaccinated = isset($vaccination['is_vaccinated']) && (int)$vaccination['is_vaccinated'] === 1 ? 1 : 0;

                            Vaccination::create([
                                'dog_id' => $dog_db->id,
                                'vaccine_name' => $vaccination['vaccine_name'],
                                'vaccination_date' => $vaccination['vaccination_date'],
                                'next_vaccination_date' => $vaccination['next_vaccination_date'],
                                'is_vaccinated' => $is_vaccinated,
                            ]);
                        }
                    }

                    // Save Documents
                    if(isset($dog['documents']) && count($dog['documents']) > 0)
                    {
                        foreach($dog['documents'] as $document)
                        {
                            if(empty($document['name'] ?? null) || empty($document['file'] ?? null)) {
                                continue;
                            }

                            $file = $document['file'];
                            $maxSize = 10 * 1024 * 1024; // 10MB
                            $uploadResult = $this->handleFileUpload($file, 'documents', $maxSize);
                            
                            if (!$uploadResult['success']) {
                                Log::warning('Document upload failed, skipping: ' . $uploadResult['error'], [
                                    'dog_id' => $dog_db->id,
                                    'document_name' => $document['name'] ?? 'unknown',
                                ]);
                                continue; 
                            }
                            
                            DogDocument::create([
                                'dog_id' => $dog_db->id,
                                'name' => $document['name'],
                                'file_path' => $uploadResult['filename'],
                                'file_type' => $file->getClientMimeType(),
                                'file_size' => $file->getSize(),
                            ]);
                        }
                    }

                    // Save Dog Friends
                    if(isset($dog['dog_friends']) && count($dog['dog_friends']) > 0)
                    {
                        foreach($dog['dog_friends'] as $friend_id)
                        {
                            // Check if friendship already exists to prevent duplicates
                            $existing = Friend::where(function($query) use ($dog_db, $friend_id) {
                                $query->where([
                                    ['dog_id', '=', $dog_db->id],
                                    ['friend_id', '=', $friend_id],
                                ])->orWhere([
                                    ['dog_id', '=', $friend_id],
                                    ['friend_id', '=', $dog_db->id],
                                ]);
                            })->first();
                            
                            if (!$existing) {
                                Friend::create([
                                    'dog_id' => $dog_db->id,
                                    'friend_id' => $friend_id
                                ]);
                            }
                        }
                    }
                }

                // All Operations Successful - Commit Transaction
                DB::commit();
            } catch (\Exception $e) {
                // Exception Occurred During Dog Creation - Rollback Transaction
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                Log::error('Exception while creating dogs for customer', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Customer is already saved, so we show a warning but don't fail the entire operation
                Session::flash('warning', 'Der Kunde wurde erfolgreich erstellt, aber es gab Fehler beim Hinzufügen der Hunde: ' . $e->getMessage());
                return redirect()->route('admin.customers.preview', ['id' => $customer->id]);
            }
        }

        Session::flash('success', 'Der Kunde wurde erfolgreich hinzugefügt');
        return back();
    }
    

    public function check_email(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $query = Customer::where('email', $request->email);
        
        // Exclude current customer when editing
        if ($request->has('exclude_id') && $request->exclude_id) {
            $query->where('id', '!=', $request->exclude_id);
        }
        
        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Diese E-Mail-Adresse wird bereits verwendet' : 'E-Mail ist verfügbar'
        ]);
    }

    public function check_id_number(Request $request)
    {
        $request->validate([
            'id_number' => 'required',
        ]);

        $query = Customer::where('id_number', $request->id_number);
        
        // Exclude current customer when editing
        if ($request->has('exclude_id') && $request->exclude_id) {
            $query->where('id', '!=', $request->exclude_id);
        }
        
        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Diese ID-Nummer wird bereits verwendet' : 'ID-Nummer ist verfügbar'
        ]);
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:Stammkunde,Organisation',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($request->id),
            ],
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:255|unique:customers,id_number,' . $request->id,
            'title' => 'nullable|string|max:50',
            'profession' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:50',
            'veterinarian' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Der Name ist erforderlich.',
            'name.string' => 'Der Name muss ein Text sein.',
            'type.required' => 'Der Typ ist erforderlich.',
            'type.in' => 'Der Typ muss entweder Stammkunde oder Organisation sein.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'id_number.unique' => 'Diese ID-Nummer wird bereits verwendet.',
        ]);

        $customer = Customer::find($request->id);
        if (!$customer) {
            Session::flash('error', 'Kunde nicht gefunden.');
            return redirect()->back();
        }
        
        $customer->type = $request->type;
        $customer->title = $request->title;
        $customer->profession = $request->profession;
        $customer->name = $request->name;
        $customer->email = !empty($request->email) ? trim($request->email) : null;
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
            $uploadResult = $this->handleFileUpload($request->picture, '');
            if (!$uploadResult['success']) {
                Session::flash('error', 'Fehler beim Hochladen des Kundenbildes: ' . $uploadResult['error']);
                return back();
            }
            $customer->picture = $uploadResult['filename'];
        }

        try {
            DB::beginTransaction();
            
            // Save customer updates
            $customer->save();

            // Sync customer update to HelloCash
            if (!empty($customer->hellocash_customer_id)) {
                // Update existing customer in HelloCash
                $result = $this->hellocashService->updateUser($customer->hellocash_customer_id, $customer);
                
                if (!$result['success']) {
                    // HelloCash Update Failed - Rollback Transaction
                    DB::rollBack();
                    $errorMessage = $result['error'] ?? 'Unbekannter Fehler bei der Registrierkasse-Aktualisierung';
                    Log::error('HelloCash update failed during customer update', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'hellocash_customer_id' => $customer->hellocash_customer_id,
                        'error' => $errorMessage,
                    ]);
                    Session::flash('error', 'Der Kunde konnte nicht aktualisiert werden: ' . $errorMessage);
                    return redirect()->back()->withInput($request->except('picture'));
                }
            } else {
                // Create new customer in HelloCash if they don't have an ID yet
                $result = $this->hellocashService->createUser($customer);
                
                if (!$result['success'] || empty($result['user_id'])) {
                    // HelloCash Sync Failed - Rollback Transaction
                    DB::rollBack();
                    $errorMessage = $result['error'] ?? 'Unbekannter Fehler bei der Registrierkasse-Synchronisation';
                    Log::error('HelloCash sync failed during customer update', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'error' => $errorMessage,
                    ]);
                    Session::flash('error', 'Der Kunde konnte nicht aktualisiert werden: ' . $errorMessage);
                    return redirect()->back()->withInput($request->except('picture'));
                }
                
                // Set HelloCash ID and save
                $customer->hellocash_customer_id = (int)$result['user_id'];
                $customer->save();
            }

            // All Operations Successful - Commit Transaction
            DB::commit();
        } catch (\Exception $e) {
            // Exception Occurred - Rollback Transaction if Transaction was Started
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Exception while updating customer with HelloCash sync', [
                'customer_id' => $customer->id ?? null,
                'customer_name' => $customer->name ?? 'Unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Session::flash('error', 'Fehler beim Aktualisieren des Kunden. Bitte versuchen Sie es erneut.');
            return redirect()->back()->withInput($request->except('picture'));
        }
        
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
            // Limit to prevent memory issues - only process first 50 dogs
            $dogsLimited = $dogs->take(50);
            
            foreach($dogsLimited as $item)
            {
                // Limit reservations per dog to prevent memory issues
                $reservations = Reservation::where('dog_id', $item->id)
                    ->orderBy('checkin_date', 'desc')
                    ->limit(100)
                    ->get();
                if(count($reservations) > 0)
                {
                    foreach($reservations as $reservation)
                    {
                        // Limit payments per reservation to prevent memory issues
                        $payments_raw = Payment::where('res_id', $reservation->id)
                            ->with('settlementsReceived')
                            ->orderBy('created_at', 'desc')
                            ->limit(50)
                            ->get();
                        if(count($payments_raw) > 0)
                        {
                            foreach($payments_raw as $payment)
                            {
                                $paymentData = $payment->toArray();
                                $paymentData['dog'] = $item->name;
                                $paymentData['dog_id'] = $item->id;
                                $paymentData['checkin'] = $reservation->checkin_date;
                                $paymentData['checkout'] = $reservation->checkout_date;

                                // Calculate remaining_amount and advance_payment if not set
                                $cost = isset($paymentData['cost']) ? (float)$paymentData['cost'] : 0.0;
                                $received = isset($paymentData['received_amount']) ? (float)$paymentData['received_amount'] : 0.0;
                                
                                if (!isset($paymentData['remaining_amount']) || $paymentData['remaining_amount'] === null) {
                                    if ($received > $cost) {
                                        $paymentData['remaining_amount'] = 0;
                                        $paymentData['advance_payment'] = $received - $cost;
                                    } else {
                                        $paymentData['remaining_amount'] = $cost - $received;
                                        $paymentData['advance_payment'] = 0;
                                    }
                                } else {
                                    $paymentData['remaining_amount'] = (float)$paymentData['remaining_amount'];
                                    if (!isset($paymentData['advance_payment']) || $paymentData['advance_payment'] === null) {
                                        if ($received > $cost) {
                                            $paymentData['advance_payment'] = $received - $cost;
                                        } else {
                                            $paymentData['advance_payment'] = 0;
                                        }
                                    }
                                }

                                // Calculate effective remaining (accounting for settlements)
                                $originalRemaining = (float)($paymentData['remaining_amount'] ?? 0);
                                $settledAmount = (float) $payment->settlementsReceived->sum('amount_settled');
                                $paymentData['effective_remaining'] = max(0, round($originalRemaining - $settledAmount, 2));
                                $paymentData['original_remaining'] = $originalRemaining;
                                $paymentData['settled_amount'] = $settledAmount;

                                $paymentData['plan_cost'] = isset($paymentData['plan_cost']) ? (float)$paymentData['plan_cost'] : 0.0;
                                $paymentData['special_cost'] = isset($paymentData['special_cost']) ? (float)$paymentData['special_cost'] : 0.0;
                                $paymentData['cost'] = isset($paymentData['cost']) ? (float)$paymentData['cost'] : 0.0;
                                $paymentData['vat_amount'] = isset($paymentData['vat_amount']) ? (float)$paymentData['vat_amount'] : 0.0;
                                $paymentData['received_amount'] = isset($paymentData['received_amount']) ? (float)$paymentData['received_amount'] : 0.0;
                                $paymentData['discount'] = isset($paymentData['discount']) ? (float)$paymentData['discount'] : 0.0;
                                $paymentData['discount_amount'] = isset($paymentData['discount_amount']) ? (float)$paymentData['discount_amount'] : 0.0;

                                $payments[] = $paymentData;
                            }
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

        // Sort payments by created_at descending (newest first)
        usort($payments, function($a, $b) {
            $dateA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
            $dateB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
            return $dateB - $dateA; // Descending order (newest first)
        });

        // Use customer balance column directly (accounts for settlements and wallet)
        $balanceService = new \App\Services\CustomerBalanceService();
        $customerBalance = $balanceService->getBalance($id);

        return view('admin.customer.preview', compact('customer' , 'dogs', 'payments', 'customerBalance'));
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
            try {
                $picture = $request->picture;
                $originalName = $picture->getClientOriginalName();
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $picture_name = uniqid() . '_' . $sanitizedName;
                
                $uploadPath = public_path('uploads/users/dogs');
                if (!file_exists($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                    }
                }
                
                $picture->move($uploadPath, $picture_name);
                $photo = $picture_name;
            } catch (\Exception $e) {
                \Log::error('Dog picture upload failed: ' . $e->getMessage());
                Session::flash('error', 'Fehler beim Hochladen des Hundebildes: ' . $e->getMessage());
                return back();
            }
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

        // Saving dog visits to db (initial values)
        $visitCounter = new \App\Services\VisitCounterService();
        $visits = (isset($request->visits) && $request->visits != null) ? (int)$request->visits : 0;
        $stay = (isset($request->days) && $request->days != null) ? (int)$request->days : 0;
        $visitCounter->setInitialCounts($dog->id, $visits, $stay);

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
                    try {
                        $originalName = $picture->getClientOriginalName();
                        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                        $picture_name = uniqid() . '_' . $sanitizedName;
                        
                        $uploadPath = public_path('uploads/users/pickup');
                        if (!file_exists($uploadPath)) {
                            if (!mkdir($uploadPath, 0755, true)) {
                                throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                            }
                        }
                        
                        $picture->move($uploadPath, $picture_name);
                        $filename = $picture_name;
                    } catch (\Exception $e) {
                        \Log::error('Pickup picture upload failed: ' . $e->getMessage());
                        // Continue without picture rather than failing entire operation
                        $filename = null;
                    }
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

        // Saving vaccinations to db
        if(isset($request->vaccine_name) && is_array($request->vaccine_name) && count($request->vaccine_name) > 0)
        {
            for($i = 0; $i < count($request->vaccine_name); $i++)
            {
                // Skip empty entries
                if(empty($request->vaccine_name[$i]) || empty($request->vaccination_date[$i]) || empty($request->next_vaccination_date[$i]))
                {
                    continue;
                }

                // Check if this vaccination is marked as vaccinated
                // The checkbox sends the last value (1 if checked, 0 from hidden if not checked)
                $is_vaccinated = isset($request->is_vaccinated[$i]) && $request->is_vaccinated[$i] == 1 ? 1 : 0;

                Vaccination::create([
                    'dog_id' => $dog->id,
                    'vaccine_name' => $request->vaccine_name[$i],
                    'vaccination_date' => $request->vaccination_date[$i],
                    'next_vaccination_date' => $request->next_vaccination_date[$i],
                    'is_vaccinated' => $is_vaccinated
                ]);
            }
        }

        // Saving documents to db
        if(isset($request->document_name) && is_array($request->document_name) && count($request->document_name) > 0)
        {
            for($i = 0; $i < count($request->document_name); $i++)
            {
                if(empty($request->document_name[$i]) || !isset($request->document_file[$i])) {
                    continue;
                }

                try {
                    $file = $request->document_file[$i];
                    
                    // Validate file size before processing (max 10MB)
                    $maxSize = 10 * 1024 * 1024; // 10MB
                    if ($file->getSize() > $maxSize) {
                        \Log::warning('Document file too large: ' . $file->getSize() . ' bytes');
                        continue; // Skip this file
                    }
                    
                    $fileType = $file->getClientMimeType();
                    $fileSize = $file->getSize();
                    
                    $originalName = $file->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $filename = uniqid() . '_' . $sanitizedName;
                    
                    $uploadPath = public_path('uploads/users/documents');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                        }
                    }
                    
                    $file->move($uploadPath, $filename);
                    
                    // Verify file was moved successfully
                    $fullPath = $uploadPath . '/' . $filename;
                    if (!file_exists($fullPath)) {
                        throw new \Exception('Datei wurde nicht erfolgreich hochgeladen');
                    }
                    
                    DogDocument::create([
                        'dog_id' => $dog->id,
                        'name' => $request->document_name[$i],
                        'file_path' => $filename,
                        'file_type' => $fileType,
                        'file_size' => $fileSize,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Document upload failed: ' . $e->getMessage(), [
                        'dog_id' => $dog->id,
                        'document_name' => $request->document_name[$i] ?? 'unknown',
                    ]);
                    // Continue with other documents rather than failing entire operation
                    continue;
                }
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

        $dog = Dog::with(['visit' , 'pickups', 'reg_plan_obj', 'customer', 'vaccinations', 'documents'])->find($id);

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
            try {
                $picture = $request->picture;
                $originalName = $picture->getClientOriginalName();
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $picture_name = uniqid() . '_' . $sanitizedName;
                
                $uploadPath = public_path('uploads/users/dogs');
                if (!file_exists($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                    }
                }
                
                $picture->move($uploadPath, $picture_name);
                $dog->picture = $picture_name;
            } catch (\Exception $e) {
                \Log::error('Dog picture upload failed: ' . $e->getMessage());
                Session::flash('error', 'Fehler beim Hochladen des Hundebildes: ' . $e->getMessage());
                return back();
            }
        }

        $dog->save();

        //Updating Visits info (initial values - these are the base counts)
        $visitCounter = new \App\Services\VisitCounterService();
        $visits = (isset($request->visits) && $request->visits != null) ? (int)$request->visits : 0;
        $days = (isset($request->days) && $request->days != null) ? (int)$request->days : 0;
        $visitCounter->setInitialCounts($request->id, $visits, $days);

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
        try {
            $dog = Dog::find($request->id);
            if (!$dog) {
                Session::flash('error', 'Hund nicht gefunden');
                return back();
            }

            // Get all reservations for this dog
            $reservations = Reservation::where('dog_id', $request->id)->with('payments')->get();
            
            foreach($reservations as $reservation)
            {
                // If reservation has payments: soft delete
                // If reservation has no payments: hard delete
                if($reservation->payments && $reservation->payments->count() > 0)
                {
                    $reservation->delete(); // Soft delete
                    
                    // Soft delete all payments for this reservation
                    foreach($reservation->payments as $payment)
                    {
                        $payment->delete(); // Soft delete
                    }
                }
                else
                {
                    $reservation->forceDelete(); // Hard delete (no payments)
                }
            }

            // Hard delete dog documents
            $dog->documents()->delete(); // This will hard delete since documents don't have soft deletes

            // Soft delete the dog
            $dog->delete();

            Session::flash('error','Hund wurde erfolgreich gelöscht');
        } catch (\Exception $e) {
            \Log::error('Error deleting dog: ' . $e->getMessage(), [
                'dog_id' => $request->id,
                'trace' => $e->getTraceAsString()
            ]);
            Session::flash('error', 'Fehler beim Löschen des Hundes. Bitte versuchen Sie es erneut.');
        }
        
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
                try {
                    $picture = $request->pick_file[$i];
                    $originalName = $picture->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $picture_name = uniqid() . '_' . $sanitizedName;
                    
                    $uploadPath = public_path('uploads/users/pickup');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                        }
                    }
                    
                    $picture->move($uploadPath, $picture_name);
                    $pickup->picture = $picture_name;
                } catch (\Exception $e) {
                    \Log::error('Pickup picture upload failed: ' . $e->getMessage());
                    // Continue without picture rather than failing entire operation
                }
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
            try {
                $picture = $request->file;
                $originalName = $picture->getClientOriginalName();
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $picture_name = uniqid() . '_' . $sanitizedName;
                
                $uploadPath = public_path('uploads/users/pickup');
                if (!file_exists($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                    }
                }
                
                $picture->move($uploadPath, $picture_name);
                $pickup->picture = $picture_name;
            } catch (\Exception $e) {
                \Log::error('Pickup picture upload failed: ' . $e->getMessage());
                Session::flash('error', 'Fehler beim Hochladen des Pickup-Bildes: ' . $e->getMessage());
                return back();
            }
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

    public function v_v_search(Request $request)
    {
        if(!General::permissions('Verstorbene Hunde'))
        {
            return response()->json([]);
        }

        $keyword = $request->input('keyword', '');
        $type = $request->input('type', 'both'); // 'adopted', 'died', or 'both'

        $query = Dog::with('customer')
            ->where(function($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('chip_number', 'like', '%' . $keyword . '%')
                  ->orWhere('compatible_breed', 'like', '%' . $keyword . '%')
                  ->orWhere('health_problems', 'like', '%' . $keyword . '%')
                  ->orWhere('medication', 'like', '%' . $keyword . '%')
                  ->orWhere('allergy', 'like', '%' . $keyword . '%')
                  ->orWhereHas('customer', function($customerQuery) use ($keyword) {
                      $customerQuery->where('name', 'like', '%' . $keyword . '%')
                                   ->orWhere('id_number', 'like', '%' . $keyword . '%')
                                   ->orWhere('phone', 'like', '%' . $keyword . '%')
                                   ->orWhere('email', 'like', '%' . $keyword . '%')
                                   ->orWhere('city', 'like', '%' . $keyword . '%');
                  })
                  ->orWhereHas('pickups', function($pickupQuery) use ($keyword) {
                      $pickupQuery->where('name', 'like', '%' . $keyword . '%')
                                 ->orWhere('phone', 'like', '%' . $keyword . '%');
                  });
            });

        if ($type === 'adopted') {
            $query->where('status', 2);
        } elseif ($type === 'died') {
            $query->where('status', 3);
        } else {
            $query->whereIn('status', [2, 3]);
        }

        $dogs = $query->orderBy('id', 'desc')->get();

        return response()->json($dogs);
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

            $daysCalculationMode = config('app.days_calculation_mode', 'inclusive');
            $daysOffset = ($daysCalculationMode === 'inclusive') ? ' + 1' : '';
            $selectRaw = 'MAX(reservations.id) as id, reservations.dog_id, SUM(GREATEST(1, DATEDIFF(date(`checkout_date`), date(`checkin_date`))' . $daysOffset . ')) AS total_stay_days';

            $dogs = Reservation::selectRaw($selectRaw)
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

        $daysCalculationMode = config('app.days_calculation_mode', 'inclusive');
        $daysOffset = ($daysCalculationMode === 'inclusive') ? ' + 1' : '';
        $selectRaw = 'MAX(id) as id, dog_id, SUM(GREATEST(1, DATEDIFF(date(`checkout_date`), date(`checkin_date`))' . $daysOffset . ')) AS total_stay_days';

        $dogs = Reservation::selectRaw($selectRaw)
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

    /**
     * Get all documents for a dog
     */
    public function get_dog_documents($dog_id)
    {
        $dog = Dog::findOrFail($dog_id);
        $documents = $dog->documents()->orderBy('created_at', 'desc')->get();
        return response()->json($documents);
    }

    /**
     * Store a new dog document
     */
    public function store_dog_document(Request $request)
    {
        $request->validate([
            'dog_id' => 'required|exists:dogs,id',
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Max 10MB, specific file types
        ]);

        $dog = Dog::findOrFail($request->dog_id);

        $file = $request->file('file');
        
        // Get file properties BEFORE moving the file
        $fileType = $file->getClientMimeType();
        $fileSize = $file->getSize();
        
        // Sanitize filename to prevent path traversal attacks
        $originalName = $file->getClientOriginalName();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $filename = uniqid() . '_' . $sanitizedName;
        
        // Ensure the directory exists
        $uploadPath = public_path('uploads/users/documents');
        if (!file_exists($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fehler beim Erstellen des Upload-Verzeichnisses',
                ], 500);
            }
        }
        
        // Move the file and handle failure
        try {
            $file->move($uploadPath, $filename);
        } catch (\Exception $e) {
            \Log::error('File upload failed: ' . $e->getMessage(), [
                'filename' => $filename,
                'path' => $uploadPath,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Hochladen der Datei: ' . $e->getMessage(),
            ], 500);
        }

        // Verify file was moved successfully before creating database record
        $fullPath = $uploadPath . '/' . $filename;
        if (!file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Datei wurde nicht erfolgreich hochgeladen',
            ], 500);
        }

        $document = DogDocument::create([
            'dog_id' => $dog->id,
            'name' => $request->name,
            'file_path' => $filename,
            'file_type' => $fileType,
            'file_size' => $fileSize,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokument erfolgreich hochgeladen',
            'document' => $document,
        ]);
    }

    /**
     * Delete a dog document
     */
    public function destroy_dog_document($id)
    {
        $document = DogDocument::findOrFail($id);
        
        // Delete the file from storage FIRST, then the record
        // This ensures if file deletion fails, we don't lose the record
        $filePath = public_path('uploads/users/documents/' . $document->file_path);
        $fileDeleted = false;
        
        if (file_exists($filePath)) {
            try {
                $fileDeleted = unlink($filePath);
            } catch (\Exception $e) {
                \Log::error('File deletion failed: ' . $e->getMessage(), [
                    'file_path' => $filePath,
                    'document_id' => $id,
                ]);
            }
        } else {
            // File doesn't exist, but we'll still delete the record
            $fileDeleted = true;
        }

        // Delete the database record
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => $fileDeleted 
                ? 'Dokument erfolgreich gelöscht' 
                : 'Dokument-Eintrag gelöscht, aber Datei konnte nicht entfernt werden',
        ]);
    }

    /**
     * Helper method to handle file uploads
     */
    private function handleFileUpload($file, $relativePath = '', $maxSize = null)
    {
        try {
            // Validate file size if specified
            if ($maxSize !== null && $file->getSize() > $maxSize) {
                return [
                    'success' => false,
                    'filename' => null,
                    'error' => 'Datei ist zu groß. Maximale Größe: ' . ($maxSize / 1024 / 1024) . ' MB',
                ];
            }

            $originalName = $file->getClientOriginalName();
            $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $filename = uniqid() . '_' . $sanitizedName;
            
            $uploadPath = public_path('uploads/users' . ($relativePath ? '/' . $relativePath : ''));
            
            if (!file_exists($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    throw new \Exception('Fehler beim Erstellen des Upload-Verzeichnisses');
                }
            }
            
            $file->move($uploadPath, $filename);
            
            return [
                'success' => true,
                'filename' => $filename,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage(), [
                'relative_path' => $relativePath,
                'original_name' => $file->getClientOriginalName(),
            ]);
            
            return [
                'success' => false,
                'filename' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
