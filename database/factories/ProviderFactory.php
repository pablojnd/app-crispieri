<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => $this->faker->company(),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->numerify('+56 9 #### ####'),
            'rut' => $this->faker->unique()->numerify('##.###.###-#'),
            'tax_id' => $this->faker->optional()->numerify('##########'),
            'type' => $this->faker->randomElement(['manufacturer', 'distributor', 'wholesaler', 'retailer']),
            'active' => $this->faker->boolean(90),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'Chile',
            'website' => $this->faker->optional()->url(),
            'observations' => $this->faker->optional()->paragraph(),
        ];
    }

    public function manufacturer()
    {
        return $this->state([
            'type' => 'manufacturer'
        ]);
    }

    public function distributor()
    {
        return $this->state([
            'type' => 'distributor'
        ]);
    }

    public function inactive()
    {
        return $this->state([
            'active' => false
        ]);
    }
}
