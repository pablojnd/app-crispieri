<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Bolivia',
                'code_iso_2' => 'BO',
                'code_iso_3' => 'BOL',
                'region' => 'South America',
                'currency_code' => 'BOB',
                'currency_name' => 'Boliviano',
                'phone_prefix' => '591',
                'is_active' => true,
            ],
            [
                'name' => 'Argentina',
                'code_iso_2' => 'AR',
                'code_iso_3' => 'ARG',
                'region' => 'South America',
                'currency_code' => 'ARS',
                'currency_name' => 'Peso Argentino',
                'phone_prefix' => '54',
                'is_active' => true,
            ],
            [
                'name' => 'Chile',
                'code_iso_2' => 'CL',
                'code_iso_3' => 'CHL',
                'region' => 'South America',
                'currency_code' => 'CLP',
                'currency_name' => 'Peso Chileno',
                'phone_prefix' => '56',
                'is_active' => true,
            ],
            [
                'name' => 'Perú',
                'code_iso_2' => 'PE',
                'code_iso_3' => 'PER',
                'region' => 'South America',
                'currency_code' => 'PEN',
                'currency_name' => 'Sol',
                'phone_prefix' => '51',
                'is_active' => true,
            ],
            [
                'name' => 'Brasil',
                'code_iso_2' => 'BR',
                'code_iso_3' => 'BRA',
                'region' => 'South America',
                'currency_code' => 'BRL',
                'currency_name' => 'Real',
                'phone_prefix' => '55',
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}
