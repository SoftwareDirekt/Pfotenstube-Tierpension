<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition()
    {
        return [
            'title' => $this->faker->words(2, true),
            'type' => 'Standard',
            'price' => $this->faker->randomFloat(2, 20, 100),
            'flat_rate' => 0,
            'discount' => 0.00,
        ];
    }

    public function flatRate()
    {
        return $this->state(function (array $attributes) {
            return [
                'flat_rate' => 1,
            ];
        });
    }
}
