<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdditionalCost;
use App\Helpers\General;
use Illuminate\Http\Request;
use Session;

class AdditionalCostsController extends Controller
{
    public function index(Request $request)
    {
        if (!General::permissions('Zusatzkosten')) {
            return to_route('admin.settings');
        }

        if ($request->ajax()) {
            $keyword = $request->keyword;
            $costs = AdditionalCost::query()
                ->where('title', 'like', '%' . $keyword . '%')
                ->orWhere('price', 'like', '%' . $keyword . '%')
                ->orderBy('id', 'desc')
                ->get();

            return $costs;
        }

        $costs = AdditionalCost::orderBy('id', 'desc')->get();

        return view('admin.additional-cost.index', compact('costs'));
    }

    public function add()
    {
        if (!General::permissions('Zusatzkosten')) {
            return to_route('admin.settings');
        }

        return view('admin.additional-cost.add');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        AdditionalCost::create([
            'title' => $request->title,
            'price' => $request->price,
        ]);

        Session::flash('success', 'Zusatzkosten erfolgreich erstellt');

        return back();
    }

    public function edit($id)
    {
        if (!General::permissions('Zusatzkosten')) {
            return to_route('admin.settings');
        }

        $cost = AdditionalCost::where('id', $id)->first();

        return view('admin.additional-cost.edit', compact('cost'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        AdditionalCost::where('id', $request->id)->update([
            'title' => $request->title,
            'price' => $request->price,
        ]);

        Session::flash('success', 'Zusatzkosten erfolgreich aktualisiert');

        return to_route('admin.additional-costs');
    }

    public function delete(Request $request)
    {
        AdditionalCost::where('id', $request->id)->delete();

        Session::flash('error', 'Zusatzkosten erfolgreich geloescht');

        return back();
    }
}
