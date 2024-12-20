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
                'name' => 'Dólar estadounidense',
                'symbol' => '$',
                'is_active' => true
            ],
            [
                'name' => 'Euro',
                'symbol' => '€',
                'is_active' => true
            ],
            [
                'name' => 'Boliviano',
                'symbol' => 'Bs',
                'is_active' => true
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}
