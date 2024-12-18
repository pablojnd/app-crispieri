<?php

namespace Database\Seeders;

use App\Models\BankCode;
use Illuminate\Database\Seeder;

class BankCodeSeeder extends Seeder
{
    public function run(): void
    {
        $bankCodes = [
            ['code' => 'B001', 'bank_name' => 'Banco Nacional'],
            ['code' => 'B002', 'bank_name' => 'Banco Internacional'],
            ['code' => 'B003', 'bank_name' => 'Banco Comercial'],
        ];

        foreach ($bankCodes as $bankCode) {
            BankCode::create($bankCode);
        }
    }
}
