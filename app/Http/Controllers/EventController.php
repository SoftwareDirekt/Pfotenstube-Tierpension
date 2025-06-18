<?php
// app/Http/Controllers/EventController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;

class EventController extends Controller
{
    public function fetchEvents(Request $request)
    {
        $events = Event::whereBetween('start', [$request->start, $request->end])
            ->get()
            ->map(function ($event) {
                $name = isset($event->user) ? $event->user->name : '';
                $event->title = $event->title . " " . $event->status . " (" . $name . ")";
                return $event;
            });
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $randomColor = $this->getRandomColorFromList();
        $event = new Event();
        $event->title = $request->title;
        $event->start = new Carbon($request->start);
        $event->end = new Carbon($request->end);
        $event->uid = $request->uid;
        $event->status = $request->status;
        $event->backgroundColor = $randomColor['backgroundColor'];
        $event->textColor = $randomColor['color'];
        $event->save();

        return response()->json([
            'backgroundColor' => $randomColor['backgroundColor'],
            'textColor' => $randomColor['color'],
            'id' => $event->id
        ]);
    }

    public function update(Request $request)
    {
        if (isset($request->eventID)){
            $id = $request->eventID;
        }else{
            $id = $request->id;
        }

        $event = Event::find($id);
        if ($event){
            if (isset($request->status)){
                $event->status = $request->status;
            }
            if (isset($request->uid)){
                $event->uid = $request->uid;
            }
            if (isset($request->start)){
                $event->start = new Carbon($request->start);
            }
            if (isset($request->end)){
                $event->end = new Carbon($request->end);
            }
            $event->save();
        }

        return response()->json(['success' => true]);
    }

    public function edit(Request $request, $id)
    {
        $event = Event::find($id);
        $event->title = $request->title;
        $event->uid = $request->uid;
        $event->status = $request->status;
        $event->save();

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $event = Event::find($request->id);
        $event->delete();

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $event = Event::find($id);
        return response()->json($event);
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
}

