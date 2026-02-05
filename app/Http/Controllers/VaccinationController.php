<?php

namespace App\Http\Controllers;

use App\Models\Vaccination;
use App\Models\Dog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VaccinationController extends Controller
{
    public function index(Dog $dog)
    {
        // Check if user has access to this dog's vaccinations
        if (!auth()->check()) {
            return response()->json(['error' => 'Nicht autorisiert'], 401);
        }
        
        return response()->json($dog->vaccinations);
    }

    public function store(Request $request)
    {
        // Check authorization
        if (!auth()->check()) {
            return response()->json(['error' => 'Nicht autorisiert'], 401);
        }
        
        try {
            $validated = $request->validate([
                'dog_id' => 'required|exists:dogs,id',
                'vaccine_name' => 'required|string|max:255',
                'vaccination_date' => 'required|date|before_or_equal:today',
                'next_vaccination_date' => 'required|date|after:vaccination_date|after:today'
            ], [
                'dog_id.required' => 'Hund ID ist erforderlich',
                'dog_id.exists' => 'Hund nicht gefunden',
                'vaccine_name.required' => 'Impfstoffname ist erforderlich',
                'vaccine_name.string' => 'Impfstoffname muss ein Text sein',
                'vaccine_name.max' => 'Impfstoffname darf maximal 255 Zeichen haben',
                'vaccination_date.required' => 'Impfdatum ist erforderlich',
                'vaccination_date.date' => 'Impfdatum muss ein gültiges Datum sein',
                'vaccination_date.before_or_equal' => 'Impfdatum darf nicht in der Zukunft liegen',
                'next_vaccination_date.required' => 'Nächstes Impfdatum ist erforderlich',
                'next_vaccination_date.date' => 'Nächstes Impfdatum muss ein gültiges Datum sein',
                'next_vaccination_date.after' => 'Nächstes Impfdatum muss nach dem Impfdatum und heute liegen'
            ]);

            $vaccination = Vaccination::create($validated);
            
            // Create notification for upcoming vaccination
            $dog = Dog::find($validated['dog_id']);
            $this->createVaccinationNotification($dog, $vaccination);

            return response()->json([
                'success' => true,
                'message' => 'Impfung erfolgreich gespeichert',
                'vaccination' => $vaccination
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Vaccination store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Speichern der Impfung: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Vaccination $vaccination)
    {
        // Check authorization
        if (!auth()->check()) {
            return response()->json(['error' => 'Nicht autorisiert'], 401);
        }
        
        try {
            // Delete related notifications
            Notification::where('vaccination_id', $vaccination->id)->delete();
            $vaccination->delete();

            return response()->json([
                'success' => true,
                'message' => 'Impfung erfolgreich gelöscht'
            ]);
        } catch (\Exception $e) {
            Log::error('Vaccination delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Löschen der Impfung: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleVaccinationStatus(Request $request, Vaccination $vaccination)
    {
        // Check authorization
        if (!auth()->check()) {
            return response()->json(['error' => 'Nicht autorisiert'], 401);
        }
        
        try {
            $request->validate([
                'is_vaccinated' => 'required|boolean'
            ], [
                'is_vaccinated.required' => 'Impfstatus ist erforderlich',
                'is_vaccinated.boolean' => 'Impfstatus muss wahr oder falsch sein'
            ]);

            $vaccination->is_vaccinated = $request->is_vaccinated;
            $vaccination->save();

            // Update notification read status
            Notification::where('vaccination_id', $vaccination->id)
                ->where('type', 'vaccination_alert')
                ->update(['read' => $vaccination->is_vaccinated ? 1 : 0]);

            return response()->json([
                'success' => true,
                'message' => $vaccination->is_vaccinated ? 'Impfung als durchgeführt markiert' : 'Impfung als nicht durchgeführt markiert',
                'is_vaccinated' => $vaccination->is_vaccinated
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Vaccination status toggle error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Impfstatus: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createVaccinationNotification(Dog $dog, Vaccination $vaccination)
    {
        $nextDate = Carbon::parse($vaccination->next_vaccination_date);

        Notification::create([
            'dog_id' => $dog->id,
            'vaccination_id' => $vaccination->id,
            'type' => 'vaccination_alert',
            'title' => 'Anstehende Impfung',
            'message' => "Impfung '{$vaccination->vaccine_name}' für {$dog->name} fällig am {$nextDate->format('d.m.Y')}",
            'read' => false,
        ]);
    }
}