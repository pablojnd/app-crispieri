<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition()
    {
        return [
            'country_name' => fake()->unique()->country(),
            'currency_id' =>  Currency::factory(),
            'region' => fake()->randomElement(['South America', 'North America', 'Central America', 'Caribbean']),
            'is_active' => fake()->boolean(90),
        ];
    }
}
