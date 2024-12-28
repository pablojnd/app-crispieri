<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\BankCode;
use App\Models\Currency;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition()
    {
        return [
            'store_id' => Store::factory(),
            'bank_code_id' => BankCode::factory(),
            'account_number' => $this->faker->unique()->numerify('#########'),
            'account_type' => $this->faker->randomElement(['checking', 'savings', 'other']),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    // Estado específico para cuenta corriente
    public function checking()
    {
        return $this->state(function (array $attributes) {
            return [
                'account_type' => 'checking',
            ];
        });
    }

    // Estado específico para cuenta de ahorro
    public function savings()
    {
        return $this->state(function (array $attributes) {
            return [
                'account_type' => 'savings',
            ];
        });
    }
}
