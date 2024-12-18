<?php

namespace Database\Seeders\Comex;

use App\Models\{ComexContainer, ComexImportOrder, ComexItem};
use Illuminate\Database\Seeder;

class ComexContainerSeeder extends Seeder
{
    public function run(): void
    {
        $importOrders = ComexImportOrder::all();

        foreach ($importOrders as $importOrder) {
            // Crear 1-3 contenedores por orden
            $containers = ComexContainer::factory(fake()->numberBetween(1, 3))->create([
                'store_id' => $importOrder->store_id,
                'import_order_id' => $importOrder->id,
            ]);

            // Obtener items de la orden
            $items = ComexItem::where('import_order_id', $importOrder->id)->get();

            if ($items->isNotEmpty()) {
                foreach ($containers as $container) {
                    // Asociar items al contenedor con datos en la tabla pivote
                    $selectedItems = $items->random(fake()->numberBetween(1, $items->count()));

                    foreach ($selectedItems as $item) {
                        // Usar los nombres correctos de las columnas en el attach
                        $container->items()->attach($item->id, [
                            'quantity' => fake()->randomFloat(2, 1, $item->quantity),
                            'weight' => fake()->randomFloat(2, 0.1, 100),
                        ]);
                    }
                }
            }
        }
    }
}
