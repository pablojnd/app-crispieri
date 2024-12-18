<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankBalance;
use Illuminate\Database\Seeder;

class BankBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $banks = Bank::all();

        foreach ($banks as $bank) {
            BankBalance::create([
                'store_id' => $bank->store_id,
                'bank_id' => $bank->id,
                'balance_date' => now(),
                'balance_usd' => fake()->randomFloat(2, 1000, 100000),
                'balance_clp' => fake()->randomFloat(2, 100000, 10000000),
                'exchange_rate' => fake()->randomFloat(4, 800, 900),
                'notes' => 'Saldo inicial',
            ]);
        }
    }
}
