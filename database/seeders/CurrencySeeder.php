<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'Dólar Estadounidense', 'code' => 'USD', 'symbol' => '$', 'decimal_places' => 2],
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'decimal_places' => 2],
            // Agrega más monedas según necesites
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}
