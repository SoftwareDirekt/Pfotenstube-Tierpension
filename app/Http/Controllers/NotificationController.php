<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        // Check authorization
        if (!auth()->check()) {
            return response()->json(['error' => 'Nicht autorisiert'], 401);
        }
        
        $today = Carbon::today();
        
        $notifications = Notification::where(function ($query) use ($today) {
            $query->where('type', 'vaccination_alert')
                ->whereHas('vaccination', function ($q) use ($today) {
                    $q->whereDate('next_vaccination_date', '<=', $today->copy()->addDays(3));
                });
        })
        ->orWhereIn('type', ['new_reservation', 'reservation_cancelled'])
        ->orderBy('created_at', 'desc')
        ->limit(20)
        ->get();

        return response()->json($notifications);
    }

    public function markAsRead(Request $request)
    {
        // Check authorization
        if (!auth()->check()) {
            return response()->json(['error' => 'Nicht autorisiert'], 401);
        }
        
        try {
            $validated = $request->validate([
                'notification_id' => 'required|exists:notifications,id'
            ], [
                'notification_id.required' => 'Benachrichtigungs-ID ist erforderlich',
                'notification_id.exists' => 'Benachrichtigung nicht gefunden'
            ]);

            $notification = Notification::findOrFail($validated['notification_id']);
            $notification->update(['read' => true]);

            return response()->json(['success' => true]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function markAllAsRead()
    {
        // Check authorization
        if (!auth()->check()) {
            return response()->json(['error' => 'Nicht autorisiert'], 401);
        }
        
        Notification::where('read', false)
            ->update(['read' => true]);

        return response()->json(['success' => true]);
    }
}
