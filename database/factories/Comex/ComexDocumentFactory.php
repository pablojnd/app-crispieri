<?php

namespace Database\Factories\Comex;

use App\Models\Store;
use App\Models\ComexDocument;
use App\Models\ComexImportOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComexDocument>
 */
class ComexDocumentFactory extends Factory
{
    protected $model = ComexDocument::class;

    public function definition(): array
    {
        $fobTotal = fake()->randomFloat(4, 1000, 50000);
        $freightTotal = fake()->randomFloat(4, 100, 5000);
        $insuranceTotal = fake()->randomFloat(4, 50, 1000);
        $cifTotal = $fobTotal + $freightTotal + $insuranceTotal;
        $totalPaid = fake()->randomFloat(4, 0, $cifTotal);

        return [
            'store_id' => Store::factory(),
            'import_order_id' => ComexImportOrder::factory(),
            'document_number' => 'DOC-' . fake()->unique()->numberBetween(1000, 9999),
            'document_type' => fake()->randomElement(['invoice', 'packing_list', 'bl', 'insurance', 'certificate', 'other']),
            'document_clause' => fake()->randomElement(['fob', 'cost_and_freight', 'cif']),
            'document_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'fob_total' => $fobTotal,
            'freight_total' => $freightTotal,
            'insurance_total' => $insuranceTotal,
            'factor' => fake()->randomFloat(9, 0.1, 2.0),
            'total_paid' => $totalPaid,
            'pending_amount' => $cifTotal - $totalPaid,
            'currency_code' => 'USD',
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
