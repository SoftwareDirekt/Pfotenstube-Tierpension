<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Helpers\General;
use App\Models\Room;

class RoomsController extends Controller
{
    public function rooms(Request $request)
    {
        if(!General::permissions('Zimmer'))
        {
            return to_route('admin.settings');
        }

        if($request->ajax())
        {
            $keyword = isset($request->keyword) ? $request->keyword :"";
            $order = isset($request->order) ? $request->order : 'desc';

            $where = [];

            if(isset($request->keyword) && $request->keyword != '')
            {
                $where = function ($query) use ($request) {
                    $query->where('number', 'like', '%' . $request->keyword . '%')
                    ->orWhere('type', 'like', '%' . $request->keyword . '%');
                };
            }

            $rooms = Room::where($where)->orderBy('id', $order)->get();
            return $rooms;

        }

        $rooms = Room::orderBy("order","asc")->get();

        return view ('admin.room.index' , compact('rooms'));
    }
    public function add_rooms()
    {
        if(!General::permissions('Zimmer'))
        {
            return to_route('admin.settings');
        }

        return view('admin.room.add');
    }
    public function post_rooms(Request $request)
    {
        $request->validate([
            'room_number' => 'required',
            'type' => 'required',
            'capacity' => 'required'
        ]);

        $room = Room::orderBy('id' , 'desc')->first();
        if($room){
            $order = (int)$room->order + 1;
        }
        else{
            $order = 1;
        }

        Room::create([
            'number' => $request->room_number,
            'type' => $request->type,
            'capacity' => $request->capacity,
            'order' => $order,
        ]);

        Session::flash('success', 'Raum erfolgreich aktualisiert');
        return back();
    }
    public function edit_room($id)
    {
        if(!General::permissions('Zimmer'))
        {
            return to_route('admin.settings');
        }

        $room = Room::where('id' , $id)->first();
        return view ('admin.room.edit', compact('room'));
    }
    public function update_room(Request $request)
    {
        $request->validate([
            'room_number' => 'required',
            'type' => 'required',
            'capacity' => 'required',
            'status' => 'required'
        ]);

        Room::where('id', $request->id)->update([
            'number'=> $request->room_number,
            'type'=> $request->type,
            'capacity'=> $request->capacity,
            'status'=> $request->status
        ]);

        Session::flash('success', 'Raum erfolgreich aktualisiert');
        return to_route('admin.rooms');
    }
    public function delete_room(Request $request)
    {
        Room::where('id', $request->id)->delete();

        Session::flash('error', 'Raum erfolgreich gelöscht');
        return back();
    }

    public function update_room_order(Request $request)
    {
        $order = $request->input('order');

        foreach ($order as $index => $id) {
            Room::where('id', $id)->update(['order' => $index + 1]);
        }
        return response()->json(['success' => true]);
    }
    public function clean_room(Request $request)
    {
            $request->validate([
                'room_id' => 'required|exists:rooms,id',
                'cleaning_status' => 'required|integer|min:0|max:2'
            ]);

            $room = Room::findOrFail($request->room_id);

            // Toggle the cleaning status (0, 1, 2)
            if ($room->cleaning_status == 0) {
                $room->cleaning_status = 1; // In progress
            } elseif ($room->cleaning_status == 1) {
                $room->cleaning_status = 2; // Cleaned
            } else {
                $room->cleaning_status = 0; // Not cleaned
            }
            $room->save();

            // Return updated status in JSON format
            return response()->json(['room' => $room]);
    }
    
    public function resetClean()
    {
        Room::query()->update(['cleaning_status' => 0]);
        return response()->json(['message' => 'All rooms cleaning status reset to Uncleaned']);
    }

}
