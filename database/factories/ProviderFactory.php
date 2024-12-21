<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'name' => $this->faker->company(),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'rut' => $this->faker->unique()->numerify('##.###.###-#'),
            'type' => 'distributor',
            'active' => true,
            'website' => $this->faker->optional()->url(),
            'observations' => $this->faker->optional()->paragraph()
        ];
    }

    public function distributor(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'distributor'
        ]);
    }

    public function manufacturer(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'manufacturer'
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'active' => false
        ]);
    }
}
