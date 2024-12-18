<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->country(),
            'code_iso_2' => $this->faker->unique()->countryCode(),
            'code_iso_3' => $this->faker->unique()->countryISOAlpha3(),
            'region' => $this->faker->randomElement(['South America', 'North America', 'Central America', 'Caribbean']),
            'currency_code' => $this->faker->currencyCode(),
            'currency_name' => $this->faker->currency(),
            'phone_prefix' => $this->faker->numberBetween(1, 999),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
