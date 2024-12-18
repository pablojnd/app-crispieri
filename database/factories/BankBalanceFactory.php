<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\BankBalance;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankBalanceFactory extends Factory
{
    protected $model = BankBalance::class;

    public function definition()
    {
        $bank = Bank::factory()->create();
        return [
            'store_id' => $bank->store_id,
            'bank_id' => $bank->id,
            'balance_date' => $this->faker->date(),
            'balance_usd' => $this->faker->randomFloat(2, 1000, 1000000),
            'balance_clp' => $this->faker->randomFloat(2, 100000, 100000000),
            'exchange_rate' => $this->faker->randomFloat(4, 800, 900),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
