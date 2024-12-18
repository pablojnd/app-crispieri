<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Store;
use App\Models\Currency;
use App\Models\BankCode;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todas las tiendas existentes
        $stores = Store::all();

        // Obtener o crear monedas básicas
        $currencies = Currency::all();
        if ($currencies->isEmpty()) {
            $currencies = Currency::factory()->count(3)->create();
        }

        // Obtener o crear códigos bancarios
        $bankCodes = BankCode::all();
        if ($bankCodes->isEmpty()) {
            $bankCodes = BankCode::factory()->count(5)->create();
        }

        // Crear bancos para cada tienda
        foreach ($stores as $store) {
            // Crear 2 bancos por tienda con diferentes códigos bancarios
            foreach ($bankCodes->random(2) as $bankCode) {
                Bank::create([
                    'store_id' => $store->id,
                    'bank_code_id' => $bankCode->id,
                    'currency_id' => $currencies->random()->id,
                    'account_number' => fake()->unique()->numerify($bankCode->code . '#####'),
                    'account_type' => fake()->randomElement(['checking', 'savings']),
                    'is_active' => true,
                ]);
            }
        }
    }
}
