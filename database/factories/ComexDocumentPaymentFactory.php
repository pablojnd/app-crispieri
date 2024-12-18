<?php

namespace Database\Factories;

use App\Models\{Store, Bank, ComexDocument, ComexDocumentPayment};
use Illuminate\Database\Eloquent\Factories\Factory;

class ComexDocumentPaymentFactory extends Factory
{
    protected $model = ComexDocumentPayment::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'document_id' => ComexDocument::factory(),
            'bank_id' => Bank::factory(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'exchange_rate' => fake()->randomFloat(2, 0.8, 1.2),
            'payment_status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
            'payment_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'reference_number' => fake()->bothify('PAY-####??'),
            'notes' => fake()->optional()->sentence()
        ];
    }
}
