<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->currencyCode(),
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥']),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
