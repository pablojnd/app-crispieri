<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeasurementUnit>
 */
class MeasurementUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'store_id' => Store::inRandomOrder()->first()->id,
            'name' => $this->faker->unique()->word(),
            'abbreviation' => $this->faker->unique()->lexify('??'),
            'description' => $this->faker->sentence(),
            'is_base_unit' => $this->faker->boolean(20),
            'conversion_factor' => $this->faker->optional()->randomFloat(4, 0.0001, 1000),
        ];
    }

    public function baseUnit()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_base_unit' => true,
                'conversion_factor' => 1,
            ];
        });
    }
}
