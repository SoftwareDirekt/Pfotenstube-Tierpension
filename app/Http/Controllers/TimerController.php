<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Timer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimerController extends Controller
{
    /**
     * Start a new timer
     */
    public function start(Request $request)
    {
        $request->validate([
            'duration' => 'required|integer|min:1|max:120' 
        ]);

        // Check if there's already an active timer
        $activeTimer = Timer::where('status', '1')->first();
        
        if ($activeTimer) {
            return response()->json([
                'success' => false,
                'message' => 'A timer is already running'
            ], 400);
        }

        // Create new timer
        $timer = Timer::create([
            'start_time' => Carbon::now(),
            'duration' => $request->duration * 60, // Store duration in seconds
            'status' => '1' // running
        ]);

        return response()->json([
            'success' => true,
            'timer' => [
                'id' => $timer->id,
                'start_time' => $timer->start_time,
                'duration' => $timer->duration,
                'remaining' => $timer->duration
            ]
        ]);
    }

    /**
     * Stop the active timer
     */
    public function stop(Request $request)
    {
        $request->validate([
            'timer_id' => 'required|exists:timers,id'
        ]);

        $timer = Timer::find($request->timer_id);

        if (!$timer || $timer->status == '0') {
            return response()->json([
                'success' => false,
                'message' => 'Timer not found or already stopped'
            ], 400);
        }

        // Update timer
        $timer->update([
            'end_time' => Carbon::now(),
            'status' => '0' // stopped
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timer stopped successfully'
        ]);
    }

    /**
     * Get the currently active timer
     */
    public function getActive()
    {
        $timer = Timer::where('status', '1')->first();

        if (!$timer) {
            return response()->json([
                'success' => false,
                'message' => 'No active timer'
            ]);
        }

        // Calculate remaining time
        $startTime = Carbon::parse($timer->start_time);
        $elapsed = $startTime->diffInSeconds(Carbon::now());
        $remaining = max(0, $timer->duration - $elapsed);

        return response()->json([
            'success' => true,
            'timer' => [
                'id' => $timer->id,
                'start_time' => $timer->start_time,
                'duration' => $timer->duration,
                'elapsed' => $elapsed,
                'remaining' => $remaining
            ]
        ]);
    }

    /**
     * Complete the timer (called when countdown reaches zero)
     */
    public function complete(Request $request)
    {
        $request->validate([
            'timer_id' => 'required|exists:timers,id'
        ]);

        $timer = Timer::find($request->timer_id);

        if (!$timer) {
            return response()->json([
                'success' => false,
                'message' => 'Timer not found'
            ], 400);
        }

        // Update timer
        $timer->update([
            'end_time' => Carbon::now(),
            'status' => '0' // stopped/completed
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timer completed successfully'
        ]);
    }
}
