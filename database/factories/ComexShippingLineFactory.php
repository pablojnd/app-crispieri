<?php

namespace Database\Factories;

use App\Models\ComexShippingLine;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComexShippingLineFactory extends Factory
{
    protected $model = ComexShippingLine::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            // 'import_order_id' => null,
            'name' => $this->faker->company() . ' Shipping',
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            // 'estimated_departure' => $this->faker->dateTimeBetween('now', '+1 year'),
            // 'actual_departure' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            // 'estimated_arrival' => $this->faker->dateTimeBetween('+1 year', '+2 years'),
            // 'actual_arrival' => $this->faker->optional()->dateTimeBetween('+1 year', '+2 years'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function active()
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive()
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
