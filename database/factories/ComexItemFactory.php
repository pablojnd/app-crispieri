<?php

namespace Database\Factories;

use App\Models\{Store, Product, ComexItem, ComexImportOrder};
use Illuminate\Database\Eloquent\Factories\Factory;

class ComexItemFactory extends Factory
{
    protected $model = ComexItem::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 1000);
        $unitPrice = fake()->randomFloat(4, 10, 1000);
        $totalPrice = $quantity * $unitPrice;

        return [
            'store_id' => Store::factory(),
            'import_order_id' => ComexImportOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'cif_unit' => fake()->optional()->randomFloat(4, 1, 100),
        ];
    }
}
