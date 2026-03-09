<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dogs = Dog::where('customer_id', $request->user()->customer_id)
            ->whereNull('died')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'dogs' => $dogs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|date',
            'race' => 'nullable|string|max:255',
            'chip_number' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
        ]);

        $dog = Dog::create([
            'customer_id' => $request->user()->customer_id,
            'name' => $data['name'],
            'age' => $data['age'] ?? null,
            'compatible_breed' => $data['race'] ?? null,
            'chip_number' => $data['chip_number'] ?? null,
            'gender' => $data['gender'] ?? null,
            'weight' => $data['weight'] ?? null,
            'picture' => 'no-user-picture.gif',
        ]);

        return response()->json([
            'success' => true,
            'dog' => $dog,
        ], 201);
    }
}
