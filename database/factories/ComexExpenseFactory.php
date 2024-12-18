<?php

namespace Database\Factories;

use App\Models\{Store, Currency, ComexExpense, ComexImportOrder};
use Illuminate\Database\Eloquent\Factories\Factory;

class ComexExpenseFactory extends Factory
{
    protected $model = ComexExpense::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'import_order_id' => ComexImportOrder::factory(),
            'currency_id' => Currency::factory(),
            'expense_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'expense_type' => fake()->randomElement([
                'gate_in',
                'thc',
                'manifest_opening',
                'guarantee',
                'liability_letter',
                'bl_issuance',
                'demurrage',
                'container_movement',
                'cranes',
                'unloading',
                'other'
            ]),
            'expense_quantity' => fake()->randomFloat(2, 1, 100),
            'expense_amount' => fake()->randomFloat(2, 100, 5000),
            'notes' => fake()->optional()->sentence()
        ];
    }
}
