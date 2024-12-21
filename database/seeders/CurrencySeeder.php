<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'name' => 'Dólar Estadounidense',
                'code' => 'USD',
                'symbol' => '$'
            ],
            [
                'name' => 'Euro',
                'code' => 'EUR',
                'symbol' => '€'
            ],
            [
                'name' => 'Peso Chileno',
                'code' => 'CLP',
                'symbol' => '$'
            ]
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency + ['is_active' => true]);
        }
    }
}
