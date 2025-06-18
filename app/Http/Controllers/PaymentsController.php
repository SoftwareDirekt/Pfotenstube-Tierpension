<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Session;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Helpers\General;

class PaymentsController extends Controller
{
    public function payment(Request $request)
    {
        if(!General::permissions('Zahlung'))
        {
            return to_route('admin.settings');
        }

        $payments = Payment::with(['reservation' => function($query) {
            $query->with(['dog' => function ($qry){
                $qry->with('customer');
            }]);
        }]);

        if ($request->filled('year') && $request->input('year') != 'all') {
            $payments = $payments->whereYear('created_at', $request->input('year'));
        }

        if ($request->filled('month') && $request->input('month') != 'all') {
            $payments = $payments->whereMonth('created_at', $request->input('month'));
        }

        if(isset($_GET['st']) && $_GET['st'] != 'alle')
        {
            $payments = $payments->where('status', $_GET['st']);
        }

        $payments = $payments->orderBy('id','desc')->paginate(30);

        foreach($payments as $obj)
        {
            if(!isset($obj->reservation->dog))
            {
                continue;
            }
            $customer_id = $obj->reservation->dog->customer_id;

            $records = \DB::select("select payments.received_amount as amount,payments.cost as cost, payments.discount as discount from payments,dogs,customers,reservations WHERE reservations.id=payments.res_id and reservations.dog_id=dogs.id and customers.id=dogs.customer_id and customers.id=$customer_id");
            $totalAmount = 0;
            foreach($records as $record)
            {
                $amount         = abs($record->amount);
                $cost           = abs($record->cost);

                $remaining = $amount - $cost;
                
                $totalAmount = $totalAmount+$remaining;
                $obj->remaining_amount = $totalAmount;
            }
        }

        return view ("admin.payment.index", compact('payments'));
    }

    public function update_payment(Request $request)
    {
        $request->validate([
            'type'=> 'required',
            'cost'=> 'required',
            'status' => 'required'
            // 'received_amount'=> 'required',
            // 'discount'=> 'required',
            // 'discount_amount'=> 'required',
            // 'status' => 'required'
        ]);

        $payment = Payment::find($request->id);
        $payment->type = $request->type;
        $payment->cost = $request->cost;
        $payment->received_amount = $request->received_amount;
        $payment->status = $request->status;
        // $payment->discount = $request->discount;
        // $payment->discount_amount = $request->discount_amount;
        // $payment->status = $request->status;
        $payment->save();

        Session::flash('success','Zahlung erfolgreich aktualisiert');
        return back();
    }

    public function delete_payment(Request $request)
    {
        Payment::where('id', $request->id)->delete();

        Session::flash('error','Zahlung erfolgreich gelöscht');
        return back();
    }
}
