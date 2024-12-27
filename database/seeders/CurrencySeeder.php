<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        $currencies = [
            ['code_adu' => 134, 'name' => 'BOLIVAR'],
            ['code_adu' => 4, 'name' => 'BOLIVIANO'],
            ['code_adu' => 37, 'name' => 'CHELIN'],
            ['code_adu' => 51, 'name' => 'CORONA DIN'],
            ['code_adu' => 96, 'name' => 'CORONA NOR'],
            ['code_adu' => 113, 'name' => 'CORONA SC'],
            ['code_adu' => 5, 'name' => 'CRUZEIRO REAL'],
            ['code_adu' => 139, 'name' => 'DIRHAM'],
            ['code_adu' => 36, 'name' => 'DOLAR AUST'],
            ['code_adu' => 6, 'name' => 'DOLAR CAN'],
            ['code_adu' => 127, 'name' => 'DÓLAR HK'],
            ['code_adu' => 97, 'name' => 'DÓLAR NZ'],
            ['code_adu' => 136, 'name' => 'DÓLAR SIN'],
            ['code_adu' => 138, 'name' => 'DÓLAR TAI'],
            ['code_adu' => 13, 'name' => 'DOLAR USA'],
            ['code_adu' => 131, 'name' => 'DRACMA'],
            ['code_adu' => 133, 'name' => 'ESCUDO'],
            ['code_adu' => 142, 'name' => 'EURO'],
            ['code_adu' => 64, 'name' => 'FLORIN'],
            ['code_adu' => 40, 'name' => 'FRANCO BEL'],
            ['code_adu' => 58, 'name' => 'FRANCO FR'],
            ['code_adu' => 82, 'name' => 'FRANCO SZ'],
            ['code_adu' => 23, 'name' => 'GUARANI'],
            ['code_adu' => 102, 'name' => 'LIBRA EST'],
            ['code_adu' => 71, 'name' => 'LIRA'],
            ['code_adu' => 30, 'name' => 'MARCO AL'],
            ['code_adu' => 57, 'name' => 'MARCO FIN'],
            ['code_adu' => 24, 'name' => 'NUEVO SOL'],
            ['code_adu' => 900, 'name' => 'OTRAS NO ESPECIFICADAS'],
            ['code_adu' => 53, 'name' => 'PESETA'],
            ['code_adu' => 1, 'name' => 'PESO ARG'],
            ['code_adu' => 200, 'name' => 'PESO CL', 'is_active' => false], // Marcado como inactivo por "disabled"
            ['code_adu' => 129, 'name' => 'PESO COL'],
            ['code_adu' => 132, 'name' => 'PESO MEX'],
            ['code_adu' => 26, 'name' => 'PESO URUG'],
            ['code_adu' => 128, 'name' => 'RAND'],
            ['code_adu' => 48, 'name' => 'RENMINBI'],
            ['code_adu' => 137, 'name' => 'RUPIA INDIA'],
            ['code_adu' => 130, 'name' => 'SUCRE'],
            ['code_adu' => 72, 'name' => 'YEN'],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->insert([
                'name' => $currency['name'],
                'code' => $currency['code_adu'],
                'code_adu' => null,
                'is_active' => $currency['is_active'] ?? true, // Por defecto activo si no se especifica
            ]);
        }
    }
}
