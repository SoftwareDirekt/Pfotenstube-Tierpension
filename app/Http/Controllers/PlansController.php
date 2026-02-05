<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Helpers\General;

class PlansController extends Controller
{
    public function plans(Request $request)
    {
        if(!General::permissions('Preisplane'))
        {
            return to_route('admin.settings');
        }

        if($request->ajax())
        {
            $keyword = $request->keyword;
            $plans = Plan::where('title', 'like', '%'.$keyword.'%')
                ->orWhere('type', 'like', '%'. $keyword. '%')
                ->orderBy('id', 'desc')->get();
            return $plans;
        }

        $plan = Plan::orderBy("id","desc")->get();
        return view("admin.plan.index", compact("plan"));
    }

    public function add_plan()
    {
        if(!General::permissions('Preisplane'))
        {
            return to_route('admin.settings');
        }

        return view("admin.plan.add");
    }

    public function post_plan(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        $flatrate = null;
        if(isset($request->flat_rate))
        {
           $flatrate = 1; 
        }

        Plan::create([
            'title'=> $request->title,
            'type'=> $request->type,
            'price'=> $request->price,
            'flat_rate' => $flatrate
        ]);

        Session::flash('success','Preisplan erfolgreich erstellt');
        return back();
    }

    public function edit_plan($id)
    {
        if(!General::permissions('Preisplane'))
        {
            return to_route('admin.settings');
        }

        $plan = Plan::where('id' , $id)->first();
        return view ('admin.plan.edit', compact('plan'));
    }

    public function update_plan(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        $flat_rate = null;
        if(isset($request->flat_rate))
        {
            $flat_rate = 1;
        }

        Plan::where('id', $request->id)->update([
            'title'=> $request->title,
            'type'=> $request->type,
            'price'=> $request->price,
            'flat_rate'=> $flat_rate
        ]);

        Session::flash('success','Preisplan erfolgreich aktualisiert');
        return to_route('admin.plans');
    }
    
    public function delete_plan(Request $request)
    {
        Plan::where('id', $request->id)->delete();

        Session::flash('error', 'Preisplan erfolgreich gelöscht');
        return back();
    }
}
