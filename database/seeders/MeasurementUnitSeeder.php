<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Enums\MeasurementUnitType;
use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MeasurementUnitSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            // Unidades de peso
            $this->createUnits($store->id, [
                [
                    'name' => 'Kilogramo',
                    'abbreviation' => 'kg',
                    'type' => MeasurementUnitType::WEIGHT,
                    'is_base_unit' => true,
                    'conversion_factor' => 1
                ],
                [
                    'name' => 'Gramo',
                    'abbreviation' => 'g',
                    'type' => MeasurementUnitType::WEIGHT,
                    'conversion_factor' => 0.001
                ],
                [
                    'name' => 'Libra',
                    'abbreviation' => 'lb',
                    'type' => MeasurementUnitType::WEIGHT,
                    'conversion_factor' => 0.453592
                ],
            ]);

            // Unidades de volumen
            $this->createUnits($store->id, [
                [
                    'name' => 'Litro',
                    'abbreviation' => 'L',
                    'type' => MeasurementUnitType::VOLUME,
                    'is_base_unit' => true,
                    'conversion_factor' => 1
                ],
                [
                    'name' => 'Mililitro',
                    'abbreviation' => 'ml',
                    'type' => MeasurementUnitType::VOLUME,
                    'conversion_factor' => 0.001
                ],
            ]);

            // Unidades de longitud
            $this->createUnits($store->id, [
                [
                    'name' => 'Metro',
                    'abbreviation' => 'm',
                    'type' => MeasurementUnitType::LENGTH,
                    'is_base_unit' => true,
                    'conversion_factor' => 1
                ],
                [
                    'name' => 'Centímetro',
                    'abbreviation' => 'cm',
                    'type' => MeasurementUnitType::LENGTH,
                    'conversion_factor' => 0.01
                ],
            ]);

            // Unidades de conteo
            $this->createUnits($store->id, [
                [
                    'name' => 'Unidad',
                    'abbreviation' => 'u',
                    'type' => MeasurementUnitType::COUNT,
                    'is_base_unit' => true,
                    'conversion_factor' => 1
                ],
                [
                    'name' => 'Docena',
                    'abbreviation' => 'doc',
                    'type' => MeasurementUnitType::COUNT,
                    'conversion_factor' => 12
                ],
            ]);
        }
    }

    private function createUnits(int $storeId, array $units): void
    {
        foreach ($units as $unit) {
            MeasurementUnit::create(array_merge(
                $unit,
                ['store_id' => $storeId]
            ));
        }
    }
}
