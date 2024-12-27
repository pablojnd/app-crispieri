<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeasurementUnitSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['name' => 'BRAZA', 'code' => '35', 'abbreviation' => 'br', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'CARTON', 'code' => '19', 'abbreviation' => 'ctn', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'CENTENA', 'code' => '12', 'abbreviation' => 'cnt', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'DOCENA', 'code' => '11', 'abbreviation' => 'dz', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'GALON(3,785 LTS)', 'code' => '22', 'abbreviation' => 'gal', 'description' => 'Un galón equivale a 3.785 litros.', 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'GRAMO', 'code' => '7', 'abbreviation' => 'g', 'description' => null, 'is_base_unit' => true, 'conversion_factor' => 1],
            ['name' => 'GRUESA', 'code' => '26', 'abbreviation' => 'grs', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'HECTOLITRO', 'code' => '8', 'abbreviation' => 'hl', 'description' => 'Equivale a 100 litros.', 'is_base_unit' => false, 'conversion_factor' => 100],
            ['name' => 'KILATE', 'code' => '5', 'abbreviation' => 'kt', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'KILOGRAMO BRUTO', 'code' => '36', 'abbreviation' => 'kg bruto', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'KILOGRAMO NETO', 'code' => '6', 'abbreviation' => 'kg neto', 'description' => null, 'is_base_unit' => true, 'conversion_factor' => 1],
            ['name' => 'KILOWATTS-HORA', 'code' => '20', 'abbreviation' => 'kWh', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'LIBRA', 'code' => '25', 'abbreviation' => 'lb', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 0.4536],
            ['name' => 'LITRO', 'code' => '9', 'abbreviation' => 'l', 'description' => null, 'is_base_unit' => true, 'conversion_factor' => 1],
            ['name' => 'METRO CUADRADO', 'code' => '15', 'abbreviation' => 'm²', 'description' => null, 'is_base_unit' => true, 'conversion_factor' => 1],
            ['name' => 'METRO CÚBICO', 'code' => '16', 'abbreviation' => 'm³', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 1],
            ['name' => 'METRO LINEAL', 'code' => '14', 'abbreviation' => 'ml', 'description' => null, 'is_base_unit' => true, 'conversion_factor' => 1],
            ['name' => 'PAR', 'code' => '17', 'abbreviation' => 'par', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'PIE CUADRADO', 'code' => '34', 'abbreviation' => 'ft²', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 0.092903],
            ['name' => 'PIE CUBICO', 'code' => '30', 'abbreviation' => 'ft³', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 0.0283168],
            ['name' => 'PIE LINEAL', 'code' => '29', 'abbreviation' => 'ft', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 0.3048],
            ['name' => 'PULGADA LINEAL', 'code' => '31', 'abbreviation' => 'in', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 0.0254],
            ['name' => 'QUINTAL METRICO BRUTO', 'code' => '2', 'abbreviation' => 'qmb', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 100],
            ['name' => 'QUINTAL METRICO NETO', 'code' => '32', 'abbreviation' => 'qmn', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 100],
            ['name' => 'RESMA', 'code' => '27', 'abbreviation' => 'res', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'SET', 'code' => '33', 'abbreviation' => 'set', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'SIN UNIDAD DE MEDIDA', 'code' => '99', 'abbreviation' => 'none', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'TONELADA METRICA BRUTA', 'code' => '1', 'abbreviation' => 'tmb', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 1000],
            ['name' => 'TONELADA METRICA NETA', 'code' => '4', 'abbreviation' => 'tmn', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 1000],
            ['name' => 'UNIDAD', 'code' => '10', 'abbreviation' => 'u', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => 'YARDA', 'code' => '28', 'abbreviation' => 'yd', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => 0.9144],
            ['name' => '1000 KILOWATT HORA', 'code' => '3', 'abbreviation' => '1000kWh', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
            ['name' => '1000 UNIDADES', 'code' => '13', 'abbreviation' => '1000u', 'description' => null, 'is_base_unit' => false, 'conversion_factor' => null],
        ];

        foreach ($units as $unit) {
            DB::table('measurement_units')->insert([
                'name' => $unit['name'],
                'code' => $unit['code'],
                'abbreviation' => $unit['abbreviation'],
                'description' => $unit['description'],
                'is_base_unit' => $unit['is_base_unit'],
                'conversion_factor' => $unit['conversion_factor'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
