<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'PESO ARGENTINO', 'code' => 'ARS', 'code_adu' => 1, 'symbol' => '$'],
            ['name' => 'BOLIVIANO', 'code' => 'BOB', 'code_adu' => 4, 'symbol' => 'Bs.'],
            ['name' => 'REAL', 'code' => 'BRL', 'code_adu' => 5, 'symbol' => 'R$'],
            ['name' => 'DÓLAR CANADIENSE', 'code' => 'CAD', 'code_adu' => 6, 'symbol' => 'CA$'],
            ['name' => 'FRANCO FRANCÉS', 'code' => 'FRF', 'code_adu' => 10, 'symbol' => '₣'],
            ['name' => 'DÓLAR ESTADOUNIDENSE', 'code' => 'USD', 'code_adu' => 13, 'symbol' => '$'],
            ['name' => 'GUARANÍ', 'code' => 'PYG', 'code_adu' => 23, 'symbol' => '₲'],
            ['name' => 'SOL', 'code' => 'PEN', 'code_adu' => 24, 'symbol' => 'S/'],
            ['name' => 'PESO URUGUAYO', 'code' => 'UYU', 'code_adu' => 26, 'symbol' => '$U'],
            ['name' => 'DÓLAR AUSTRALIANO', 'code' => 'AUD', 'code_adu' => 36, 'symbol' => 'A$'],
            ['name' => 'YUAN', 'code' => 'CNY', 'code_adu' => 48, 'symbol' => '¥'],
            ['name' => 'CORONA DANESA', 'code' => 'DKK', 'code_adu' => 51, 'symbol' => 'kr'],
            ['name' => 'YEN', 'code' => 'JPY', 'code_adu' => 72, 'symbol' => '¥'],
            ['name' => 'FRANCO SUIZO', 'code' => 'CHF', 'code_adu' => 82, 'symbol' => 'Fr'],
            ['name' => 'CORONA NORUEGA', 'code' => 'NOK', 'code_adu' => 96, 'symbol' => 'kr'],
            ['name' => 'DÓLAR NEOZELANDÉS', 'code' => 'NZD', 'code_adu' => 97, 'symbol' => 'NZ$'],
            ['name' => 'LIBRA ESTERLINA', 'code' => 'GBP', 'code_adu' => 100, 'symbol' => '£'],
            ['name' => 'EURO', 'code' => 'EUR', 'code_adu' => 142, 'symbol' => '€'],
            ['name' => 'PESO CHILENO', 'code' => 'CLP', 'code_adu' => 200, 'symbol' => '$'],
            ['name' => 'OTRAS MONEDAS', 'code' => 'OTH', 'code_adu' => 900, 'symbol' => '$']
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency + ['is_active' => true]);
        }
    }
}
