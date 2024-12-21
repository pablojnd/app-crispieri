<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddressSeeder extends Seeder
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'rut' => fake()->unique()->numerify('##.###.###-#'),
            'tax_id' => fake()->optional()->numerify('##########'),
            'type' => fake()->randomElement(['manufacturer', 'distributor', 'wholesaler', 'retailer']),
            'active' => fake()->boolean(50),
            'website' => fake()->optional()->url(),
            'observations' => fake()->optional()->paragraph()
        ];
    }
}
