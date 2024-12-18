<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class BrandCategoryProductSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todas las tiendas existentes
        $stores = Store::all();

        if ($stores->isEmpty()) {
            throw new \Exception('No hay tiendas creadas. Ejecute StoreSeeder primero.');
        }

        foreach ($stores as $store) {
            // Obtener una unidad de medida existente para la tienda
            $measurementUnit = MeasurementUnit::where('store_id', $store->id)->first();

            if (!$measurementUnit) {
                throw new \Exception("La tienda {$store->id} no tiene unidades de medida. Ejecute MeasurementUnitSeeder primero.");
            }

            // Crear marcas para la tienda
            $brands = [];
            for ($i = 0; $i < 5; $i++) {
                $brands[] = Brand::factory()->create([
                    'store_id' => $store->id
                ]);
            }

            // Crear categorías principales
            $mainCategories = [];
            for ($i = 0; $i < 3; $i++) {
                $mainCategories[] = Category::factory()->create([
                    'store_id' => $store->id
                ]);
            }

            // Crear subcategorías
            foreach ($mainCategories as $mainCategory) {
                for ($i = 0; $i < 2; $i++) {
                    Category::factory()->create([
                        'store_id' => $store->id,
                        'parent_id' => $mainCategory->id
                    ]);
                }
            }

            // Crear productos
            foreach ($brands as $brand) {
                for ($i = 0; $i < 3; $i++) {
                    Product::factory()->create([
                        'store_id' => $store->id,
                        'brand_id' => $brand->id,
                        'category_id' => $mainCategories[array_rand($mainCategories)]->id,
                        'measurement_unit_id' => $measurementUnit->id
                    ]);
                }
            }
        }
    }
}
