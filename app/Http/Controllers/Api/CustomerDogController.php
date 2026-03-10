<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ApiJsonResponses;
use App\Models\Dog;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerDogController extends Controller
{
    use ApiJsonResponses;

    public function index(Request $request): JsonResponse
    {
        $dogs = Dog::where('customer_id', $request->user()->customer_id)
            ->whereNull('died')
            ->orderBy('name')
            ->get();

        return $this->successResponse('Hunde erfolgreich geladen.', [
            'dogs' => $dogs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        $dogsInput = isset($payload['dogs']) && is_array($payload['dogs'])
            ? $payload['dogs']
            : [$payload];

        $validator = Validator::make(['dogs' => $dogsInput], [
            'dogs' => 'required|array|min:1',
            'dogs.*.name' => 'required|string|max:255',
            'dogs.*.age' => 'nullable|date',
            'dogs.*.race' => 'nullable|string|max:255',
            'dogs.*.compatible_breed' => 'nullable|string|max:255',
            'dogs.*.chip_number' => 'nullable|string|max:255',
            'dogs.*.gender' => 'nullable|string|max:50',
            'dogs.*.weight' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validierungsfehler bei der Hundeanlage.', $validator->errors(), 422);
        }

        $plans = Plan::orderBy('id')->pluck('id')->values();
        if ($plans->count() < 2) {
            return $this->errorResponse(
                'Für die Hundeanlage werden mindestens zwei Preispläne benötigt (Tagestarif und Pensionstarif).',
                ['plans' => ['Bitte mindestens zwei Pläne im System anlegen.']],
                422
            );
        }

        $dayPlanId = (int) $plans[0];
        $regPlanId = (int) $plans[1];
        $customerId = $request->user()->customer_id;
        $createdDogs = [];

        foreach ($dogsInput as $dogData) {
            $createdDogs[] = Dog::create([
                'customer_id' => $customerId,
                'name' => $dogData['name'],
                'age' => $dogData['age'] ?? null,
                'compatible_breed' => $dogData['compatible_breed'] ?? ($dogData['race'] ?? null),
                'chip_number' => $dogData['chip_number'] ?? null,
                'gender' => $dogData['gender'] ?? null,
                'weight' => $dogData['weight'] ?? null,
                'picture' => 'no-user-picture.gif',
                'reg_plan' => $regPlanId,
                'day_plan' => $dayPlanId,
            ]);
        }

        return $this->successResponse('Hund(e) erfolgreich angelegt.', [
            'dogs' => $createdDogs,
            'count' => count($createdDogs),
        ], 201);
    }
}
