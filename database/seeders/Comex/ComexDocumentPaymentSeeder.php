<?php

namespace Database\Seeders\Comex;

use App\Models\{Bank, ComexDocument, ComexDocumentPayment};
use Illuminate\Database\Seeder;

class ComexDocumentPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = ComexDocument::all();
        $banks = Bank::all();

        foreach ($documents as $document) {
            // Crear 1-3 pagos por documento
            $paymentCount = fake()->numberBetween(1, 3);
            ComexDocumentPayment::factory($paymentCount)->create([
                'store_id' => $document->store_id,
                'document_id' => $document->id,
                'bank_id' => fn() => $banks->random()->id,
            ]);
        }
    }
}
