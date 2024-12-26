<?php

namespace Database\Factories;

use App\Models\{Store, Provider, Country, ComexImportOrder};
use Illuminate\Database\Eloquent\Factories\Factory;

class ComexImportOrderFactory extends Factory
{
    protected $model = ComexImportOrder::class;

    public function definition(): array
    {
        $orderDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'store_id' => Store::factory(),
            'provider_id' => Provider::factory(),
            'origin_country_id' => Country::factory(),
            'reference_number' => 'IMP-' . fake()->unique()->numberBetween(1000, 9999),
            'external_reference' => fake()->boolean(70) ? fake()->bothify('EXT-####') : null,
            'sve_registration_number' => fake()->boolean(60) ? fake()->bothify('SVE####??') : null,
            'type' => fake()->randomElement(['air', 'sea', 'land']),
            'status' => fake()->randomElement(['draft', 'confirmed', 'in_transit', 'in_customs', 'in_zofri', 'received', 'cancelled']),
            'order_date' => $orderDate,
        ];
    }
}
