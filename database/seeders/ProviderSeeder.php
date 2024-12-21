<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Address;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            // Crear 3 distribuidores por tienda
            Provider::factory()
                ->count(3)
                ->distributor()
                ->has(
                    Address::factory()
                        ->state(['store_id' => $store->id, 'is_default' => true])
                        ->count(1)
                )
                ->create(['store_id' => $store->id]);

            // Crear 2 fabricantes por tienda
            Provider::factory()
                ->count(2)
                ->manufacturer()
                ->has(
                    Address::factory()
                        ->state(['store_id' => $store->id, 'is_default' => true])
                        ->count(1)
                )
                ->create(['store_id' => $store->id]);

            // Crear 1 minorista inactivo por tienda
            Provider::factory()
                ->inactive()
                ->state(['type' => 'retailer'])
                ->has(
                    Address::factory()
                        ->state(['store_id' => $store->id, 'is_default' => true])
                        ->count(1)
                )
                ->create(['store_id' => $store->id]);
        }
    }
}
