<?php

namespace Database\Factories\Comex;

use App\Models\Store;
use App\Models\Country;
use App\Models\Provider;
use App\Models\ComexImportOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportOrder>
 */
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
            'status' => fake()->randomElement(['draft', 'confirmed', 'in_transit', 'in_customs', 'in_zofri', 'received']),
            'order_date' => $orderDate,
            'estimated_departure' => fake()->dateTimeBetween($orderDate, '+2 months'),
            'actual_departure' => fake()->optional(0.7)->dateTimeBetween($orderDate, '+2 months'),
            'estimated_arrival' => fake()->dateTimeBetween($orderDate, '+3 months'),
            'actual_arrival' => fake()->optional(0.5)->dateTimeBetween($orderDate, '+3 months'),
        ];
    }
}
