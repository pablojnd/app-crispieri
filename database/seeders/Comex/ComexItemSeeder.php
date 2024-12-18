<?php

namespace Database\Seeders\Comex;

use App\Models\{ComexItem, ComexImportOrder, Product};
use Illuminate\Database\Seeder;

class ComexItemSeeder extends Seeder
{
    public function run(): void
    {
        $importOrders = ComexImportOrder::all();
        $products = Product::all();

        if ($importOrders->isEmpty()) {
            $importOrders = ComexImportOrder::factory(5)->create();
        }

        if ($products->isEmpty()) {
            $products = Product::factory(10)->create();
        }

        foreach ($importOrders as $importOrder) {
            // Crear 3-8 items por orden de importación
            ComexItem::factory(fake()->numberBetween(3, 8))->create([
                'store_id' => $importOrder->store_id,
                'import_order_id' => $importOrder->id,
                'product_id' => fn() => $products->random()->id,
            ]);
        }
    }
}
