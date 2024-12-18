<?php

namespace Database\Seeders\Comex;

use App\Models\{ComexExpense, ComexImportOrder, Currency};
use Illuminate\Database\Seeder;

class ComexExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $importOrders = ComexImportOrder::all();
        $currencies = Currency::all();

        foreach ($importOrders as $importOrder) {
            ComexExpense::factory(fake()->numberBetween(2, 5))->create([
                'store_id' => $importOrder->store_id,
                'import_order_id' => $importOrder->id,
                'currency_id' => fn() => $currencies->random()->id,
            ]);
        }
    }
}
