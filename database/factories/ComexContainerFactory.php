<?php

namespace Database\Factories;

use App\Models\{Store, ComexContainer, ComexImportOrder};
use Illuminate\Database\Eloquent\Factories\Factory;

class ComexContainerFactory extends Factory
{
    protected $model = ComexContainer::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'import_order_id' => ComexImportOrder::factory(),
            'container_number' => fake()->unique()->bothify('CONT#####??'),
            'type' => fake()->randomElement(['20GP', '40GP', '40HC', 'LCL', 'REEFER', 'OPEN_TOP']),
            'weight' => fake()->randomFloat(2, 1000, 25000),
            'cost' => fake()->randomFloat(2, 500, 5000),
            // 'estimated_departure' => $this->faker->dateTimeBetween('now', '+1 year'),
            // 'actual_departure' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            // 'estimated_arrival' => $this->faker->dateTimeBetween('+1 year', '+2 years'),
            // 'actual_arrival' => $this->faker->optional()->dateTimeBetween('+1 year', '+2 years'),
            'notes' => fake()->optional(0.7)->sentence(),
        ];
    }
}
