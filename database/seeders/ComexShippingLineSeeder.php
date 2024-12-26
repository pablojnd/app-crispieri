<?php

namespace Database\Seeders;

use App\Models\ComexShippingLine;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ComexShippingLineSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            ComexShippingLine::factory()
                ->count(5)
                ->active()
                ->create(['store_id' => $store->id]);

            ComexShippingLine::factory()
                ->count(2)
                ->inactive()
                ->create(['store_id' => $store->id]);
        }
    }
}
