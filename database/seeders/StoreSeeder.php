<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 3 tiendas asignadas al usuario por defecto
        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            for ($i = 0; $i < 3; $i++) {
                $store = Store::factory()->create();
                $store->users()->attach($user->id);
            }
        }

        // Crear 3 tiendas para usuarios aleatorios
        $users = User::inRandomOrder()->take(3)->get();
        foreach ($users as $user) {
            $store = Store::factory()->create();
            $store->users()->attach($user->id);
        }
    }
}
