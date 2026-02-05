<?php

namespace Database\Factories;

use App\Models\Dog;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class DogFactory extends Factory
{
    protected $model = Dog::class;

    public function definition()
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => $this->faker->firstName(),
            'age' => $this->faker->numberBetween(1, 15),
            'weight' => $this->faker->numberBetween(5, 50),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'neutered' => $this->faker->randomElement([0, 1]),
        ];
    }
}
