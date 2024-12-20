<?php

namespace Database\Seeders;

use App\Models\ComexExpense;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ComexExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ComexExpense::factory()->count(50)->create();
    }
}
