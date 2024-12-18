<?php

namespace Database\Seeders\Comex;

use App\Models\{Store, Provider, Country, ComexImportOrder};
use Illuminate\Database\Seeder;

class ComexImportOrderSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();
        $providers = Provider::all();
        $countries = Country::all();

        if ($stores->isEmpty() || $providers->isEmpty() || $countries->isEmpty()) {
            $stores = Store::factory(2)->create();
            $providers = Provider::factory(5)->create();
            $countries = Country::factory(3)->create();
        }

        // Crear 10 órdenes de importación
        ComexImportOrder::factory(10)->create([
            'store_id' => fn() => $stores->random()->id,
            'provider_id' => fn() => $providers->random()->id,
            'origin_country_id' => fn() => $countries->random()->id,
        ]);
    }
}
