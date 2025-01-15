<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BrandCategorySeeder extends Seeder
{
    private const DEFAULT_BRANDS = [
        'Sin Marca',
        'FOSHAN LIVE',
        'FOS',
        'FOSHAN LIVE',
        'JINAN SUNNY',
        'TECKSON',
        'COBOCE',
        'SANSON',
        'ITALGRIF',
        'VAINSA',
        'YEKALON',
        'BT',
        'WASTON'
    ];

    private const DEFAULT_CATEGORIES = [
        'Sin Categoría',
        'VIDRIO',
        'CUARZO',
        'CUBIERTAS',
        'PEGAMENTOS',
        'QUINCALLERIA',
        'REVESTIMIENTOS',
        'ART. BAÑO'
    ];

    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            throw new \Exception('No hay tiendas creadas. Ejecute StoreSeeder primero.');
        }

        foreach ($stores as $store) {
            // Crear marcas predefinidas
            foreach (self::DEFAULT_BRANDS as $brandName) {
                Brand::factory()->create([
                    'store_id' => $store->id,
                    'name' => $brandName,
                    'description' => $brandName === 'Sin Marca'
                        ? 'Marca por defecto del sistema'
                        : "Marca {$brandName} registrada en el sistema",
                    'is_active' => true
                ]);
            }

            // Crear categorías predefinidas
            foreach (self::DEFAULT_CATEGORIES as $categoryName) {
                Category::factory()->create([
                    'store_id' => $store->id,
                    'name' => $categoryName,
                    'description' => $categoryName === 'Sin Categoría'
                        ? 'Categoría por defecto del sistema'
                        : "Categoría {$categoryName} registrada en el sistema",
                    'is_active' => true
                ]);
            }
        }
    }
}
