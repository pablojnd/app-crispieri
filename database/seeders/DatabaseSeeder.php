<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // User::factory()->create([
        //     'name' => 'admin User',
        //     'email' => 'admin@admin.com',
        //     'password' => bcrypt('123456'),
        // ]);

        $this->call([
            StoreSeeder::class,
            CurrencySeeder::class,
            BankCodeSeeder::class,
            BankSeeder::class,
            BankBalanceSeeder::class,
            CountrySeeder::class,
            MeasurementUnitSeeder::class,
            ProviderSeeder::class,
            BrandCategoryProductSeeder::class,
            Comex\ComexImportOrderSeeder::class,
            Comex\ComexItemSeeder::class,        // Mover ItemSeeder antes de Container y Document
            ComexShippingLineSeeder::class,
            Comex\ComexContainerSeeder::class,
            Comex\ComexDocumentSeeder::class,
            Comex\ComexDocumentPaymentSeeder::class,
            Comex\ComexExpenseSeeder::class,
            // ProductAttributeSeeder::class,
            EventSeeder::class,
        ]);
    }
}
