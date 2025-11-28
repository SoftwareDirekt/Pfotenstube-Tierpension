<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\EventColor;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Fetch events for FullCalendar
     */
    public function fetchEvents(Request $request)
    {
        $events = Event::with('user')
            ->whereBetween('start', [$request->start, $request->end])
            ->get()
            ->map(function ($event) {
                // Determine if this is an all-day event
                $isAllDay = in_array($event->status, ['Urlaub', 'Krankenstand']);
                
                // Build title based on event type
                if ($event->status === 'Andere') {
                    $title = $event->notes ? substr($event->notes, 0, 50) . (strlen($event->notes) > 50 ? '...' : '') : 'Andere';
                } else {
                    $userName = $event->user?->name ?? 'Unbekannt';
                    $title = "{$event->status} ({$userName})";
                }
                
                $eventData = [
                    'id' => $event->id,
                    'title' => $title,
                    'start' => $isAllDay 
                        ? $event->start->format('Y-m-d') 
                        : $event->start->format('Y-m-d\TH:i:s'),
                    'end' => $isAllDay 
                        ? $event->end->copy()->addDay()->format('Y-m-d')
                        : $event->end->format('Y-m-d\TH:i:s'),
                    'allDay' => $isAllDay,
                    'backgroundColor' => $event->backgroundColor,
                    'textColor' => $event->textColor,
                    'extendedProps' => [
                        'status' => $event->status,
                        'uid' => $event->uid,
                        'shift' => $event->shift,
                        'notes' => $event->notes,
                    ]
                ];
                
                // Add tooltip for "Andere" events with notes
                if ($event->status === 'Andere' && $event->notes) {
                    $eventData['extendedProps']['tooltip'] = $event->notes;
                }
                
                return $eventData;
            });

        return response()->json($events);
    }

    /**
     * Store a new event
     */
    public function store(Request $request)
    {
        $isAndere = $request->status === 'Andere';
        $isAllDay = in_array($request->status, ['Urlaub', 'Krankenstand']);

        // Dynamic validation based on event type
        $rules = [
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|in:Arbeit,Urlaub,Krankenstand,Andere',
        ];

        if ($isAndere) {
            $rules['notes'] = 'required|string|max:1000';
            $rules['uid'] = 'nullable|exists:users,id';
            $rules['start_time'] = 'nullable|date_format:H:i';
            $rules['end_time'] = 'nullable|date_format:H:i';
            $rules['shift'] = 'nullable|in:morning,evening';
        } else {
            $rules['uid'] = 'required|exists:users,id';
            $rules['notes'] = 'nullable|string|max:1000';
            
            if (!$isAllDay) {
                $rules['start_time'] = 'required|date_format:H:i';
                $rules['end_time'] = 'required|date_format:H:i';
                $rules['shift'] = 'required|in:morning,evening';
            }
        }

        $validated = $request->validate($rules);

        // Build start and end datetime
        if ($isAllDay) {
            $start = Carbon::parse($validated['date'])->startOfDay();
            $end = Carbon::parse($validated['date'])->endOfDay();
            $shift = null;
        } elseif (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
            $end = Carbon::parse("{$validated['date']} {$validated['end_time']}");
            
            // Validate that end time is after start time
            if ($end <= $start) {
                return back()->withErrors(['end_time' => 'Die Endzeit muss nach der Startzeit liegen.'])->withInput();
            }
            
            $shift = $validated['shift'] ?? null;
        } else {
            // Andere without times - use full day
            $start = Carbon::parse($validated['date'])->startOfDay();
            $end = Carbon::parse($validated['date'])->endOfDay();
            $shift = null;
        }

        $colors = EventColor::forStatus($validated['status']);

        $event = Event::create([
            'title' => null,
            'start' => $start,
            'end' => $end,
            'uid' => $validated['uid'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'backgroundColor' => $colors['backgroundColor'],
            'textColor' => $colors['textColor'],
            'shift' => $shift,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ereignis erfolgreich erstellt.',
            'event' => $event,
        ]);
    }

    /**
     * Get a single event for editing
     */
    public function show($id)
    {
        $event = Event::findOrFail($id);

        return response()->json([
            'id' => $event->id,
            'date' => $event->start->format('Y-m-d'),
            'start_time' => $event->start->format('H:i'),
            'end_time' => $event->end->format('H:i'),
            'uid' => $event->uid,
            'status' => $event->status,
            'shift' => $event->shift,
            'notes' => $event->notes,
        ]);
    }

    /**
     * Update an existing event
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $isAndere = $request->status === 'Andere';
        $isAllDay = in_array($request->status, ['Urlaub', 'Krankenstand']);

        // Dynamic validation based on event type
        $rules = [
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|in:Arbeit,Urlaub,Krankenstand,Andere',
        ];

        if ($isAndere) {
            $rules['notes'] = 'required|string|max:1000';
            $rules['uid'] = 'nullable|exists:users,id';
            $rules['start_time'] = 'nullable|date_format:H:i';
            $rules['end_time'] = 'nullable|date_format:H:i';
            $rules['shift'] = 'nullable|in:morning,evening';
        } else {
            $rules['uid'] = 'required|exists:users,id';
            $rules['notes'] = 'nullable|string|max:1000';
            
            if (!$isAllDay) {
                $rules['start_time'] = 'required|date_format:H:i';
                $rules['end_time'] = 'required|date_format:H:i';
                $rules['shift'] = 'required|in:morning,evening';
            }
        }

        $validated = $request->validate($rules);

        // Build start and end datetime
        if ($isAllDay) {
            $start = Carbon::parse($validated['date'])->startOfDay();
            $end = Carbon::parse($validated['date'])->endOfDay();
            $shift = null;
        } elseif (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
            $end = Carbon::parse("{$validated['date']} {$validated['end_time']}");
            
            // Validate that end time is after start time
            if ($end <= $start) {
                return back()->withErrors(['end_time' => 'Die Endzeit muss nach der Startzeit liegen.'])->withInput();
            }
            
            $shift = $validated['shift'] ?? null;
        } else {
            $start = Carbon::parse($validated['date'])->startOfDay();
            $end = Carbon::parse($validated['date'])->endOfDay();
            $shift = null;
        }

        $colors = EventColor::forStatus($validated['status']);

        $event->update([
            'title' => null,
            'start' => $start,
            'end' => $end,
            'uid' => $validated['uid'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'backgroundColor' => $colors['backgroundColor'],
            'textColor' => $colors['textColor'],
            'shift' => $shift,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ereignis erfolgreich aktualisiert.',
        ]);
    }

    /**
     * Delete an event
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ereignis erfolgreich gelöscht.',
        ]);
    }
}
