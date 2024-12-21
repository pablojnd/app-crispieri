<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'type' => $this->faker->randomElement(['main', 'billing', 'shipping']),
            'street_address' => $this->faker->streetAddress(),
            'street_number' => $this->faker->buildingNumber(),
            'apartment' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'is_default' => false,
            'additional_info' => $this->faker->optional()->sentence()
        ];
    }

    public function main(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'main',
            'is_default' => true,
        ]);
    }
}
