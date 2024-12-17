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

        User::factory()->create([
            'name' => 'admin User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('123456'),
        ]);

        $this->call([
            StoreSeeder::class,
        ]);
    }
}
