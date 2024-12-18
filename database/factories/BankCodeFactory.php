<?php

namespace Database\Factories;

use App\Models\BankCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankCodeFactory extends Factory
{
    protected $model = BankCode::class;

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->numerify('B###'),
            'bank_name' => $this->faker->company() . ' Bank',
            'description' => $this->faker->sentence(),
        ];
    }
}
