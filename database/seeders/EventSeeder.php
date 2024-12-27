<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Store;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            Event::create([
                'store_id' => $store->id,
                'title' => 'Evento de prueba',
                'description' => 'Este es un evento de prueba para la tienda ' . $store->name,
                'start_at' => now(),
                'end_at' => now()->addHours(2),
            ]);

            Event::create([
                'store_id' => $store->id,
                'title' => 'Reunión mensual',
                'description' => 'Reunión de planificación mensual',
                'start_at' => now()->addDays(5),
                'end_at' => now()->addDays(5)->addHours(1),
            ]);
        }

        // Agregar 10 eventos aleatorios usando el factory
        Event::factory()->count(10)->create();
    }
}
