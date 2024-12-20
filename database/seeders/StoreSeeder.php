<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\User;

class StoreSeeder extends Seeder
{
    public function run()
    {
        // Asegurarse de que el usuario admin existe
        $admin = User::firstOrCreate(
            ['email' => 'admin@crispieri.cl'],
            [
                'name' => 'Admin',
                'password' => bcrypt('123456'), // Cambia esto por una contraseña segura
            ]
        );

        // Crear tiendas por defecto
        $stores = [
            [
                'name' => 'Latorre',
                'email' => 'latorre@example.com',
                // 'logo' => 'https://via.placeholder.com/200',
                'phone' => '123-456-7890',
                'address' => '123 Latorre St',
                'city' => 'City1',
                'state' => 'State1',
                'country' => 'Country1',
                'zip_code' => '12345',
                'website' => 'https://latorre.com',
                'is_active' => true,
            ],
            [
                'name' => 'Victoria',
                'email' => 'victoria@example.com',
                // 'logo' => 'https://via.placeholder.com/200',
                'phone' => '123-456-7890',
                'address' => '123 Victoria St',
                'city' => 'City2',
                'state' => 'State2',
                'country' => 'Country2',
                'zip_code' => '12345',
                'website' => 'https://victoria.com',
                'is_active' => true,
            ],
            [
                'name' => 'Galpon',
                'email' => 'galpon@example.com',
                // 'logo' => 'https://via.placeholder.com/200',
                'phone' => '123-456-7890',
                'address' => '123 Galpon St',
                'city' => 'City3',
                'state' => 'State3',
                'country' => 'Country3',
                'zip_code' => '12345',
                'website' => 'https://galpon.com',
                'is_active' => true,
            ],
            [
                'name' => 'Antofagasta',
                'email' => 'antofagasta@example.com',
                // 'logo' => 'https://via.placeholder.com/200',
                'phone' => '123-456-7890',
                'address' => '123 Antofagasta St',
                'city' => 'City4',
                'state' => 'State4',
                'country' => 'Country4',
                'zip_code' => '12345',
                'website' => 'https://antofagasta.com',
                'is_active' => true,
            ],
        ];

        foreach ($stores as $storeData) {
            $store = Store::firstOrCreate($storeData);
            // Asignar la tienda al usuario admin
            $admin->stores()->attach($store->id);
        }
    }
}
