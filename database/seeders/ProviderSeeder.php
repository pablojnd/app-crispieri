<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todas las tiendas
        $stores = Store::all();

        foreach ($stores as $store) {
            // Crear 3 distribuidores por tienda
            Provider::factory()
                ->count(3)
                ->distributor()
                ->create(['store_id' => $store->id]);

            // Crear 2 fabricantes por tienda
            Provider::factory()
                ->count(2)
                ->manufacturer()
                ->create(['store_id' => $store->id]);

            // Crear 1 minorista inactivo por tienda
            Provider::factory()
                ->inactive()
                ->state(['type' => 'retailer'])
                ->create(['store_id' => $store->id]);
        }
    }
}
