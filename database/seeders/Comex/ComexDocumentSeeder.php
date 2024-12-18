<?php

namespace Database\Seeders\Comex;

use App\Models\{ComexDocument, ComexImportOrder, ComexContainer, ComexItem};
use Illuminate\Database\Seeder;

class ComexDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $importOrders = ComexImportOrder::all();

        foreach ($importOrders as $importOrder) {
            // Crear 2-4 documentos por orden
            $documentsData = ComexDocument::factory(fake()->numberBetween(2, 4))
                ->make([
                    'store_id' => $importOrder->store_id,
                    'import_order_id' => $importOrder->id,
                ])
                ->each(function ($document) {
                    $document->saveQuietly();
                });

            // Obtener contenedores y items de la orden
            $containers = ComexContainer::where('import_order_id', $importOrder->id)->get();
            $items = ComexItem::where('import_order_id', $importOrder->id)->get();

            // Iterar sobre los documentos creados
            ComexDocument::whereIn('id', $documentsData->pluck('id'))->get()
                ->each(function ($document) use ($items, $containers) {
                    // Procesar items
                    if ($items->isNotEmpty()) {
                        $selectedItems = $items->random(fake()->numberBetween(1, $items->count()));
                        foreach ($selectedItems as $item) {
                            $this->attachItem($document, $item);
                        }
                    }

                    // Procesar contenedores
                    if ($containers->isNotEmpty()) {
                        $this->attachContainers($document, $containers);
                    }

                    // Actualizar factor después de asociar items
                    $document->updateFactor();
                });
        }
    }

    /**
     * Adjuntar un item al documento con manejo de errores
     */
    protected function attachItem($document, $item): void
    {
        try {
            $document->items()->attach($item->id, [
                'quantity' => fake()->randomFloat(2, 1, $item->quantity),
                'cif_amount' => fake()->randomFloat(4, 10, 1000),
            ]);
        } catch (\Exception $e) {
            // Log error if needed
            \Log::warning("Error attaching item {$item->id} to document {$document->id}: " . $e->getMessage());
        }
    }

    /**
     * Adjuntar contenedores al documento con manejo de errores
     */
    protected function attachContainers($document, $containers): void
    {
        try {
            $selectedContainers = $containers->random(fake()->numberBetween(1, $containers->count()))
                ->pluck('id')
                ->toArray();

            $document->containers()->attach($selectedContainers);
        } catch (\Exception $e) {
            // Log error if needed
            \Log::warning("Error attaching containers to document {$document->id}: " . $e->getMessage());
        }
    }
}
