<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        // Atributos comunes
        $attributes = [
            'Color' => ['Rojo', 'Negro', 'Blanco', 'Azul', 'Verde', 'Amarillo'],
            'Talla' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'Material' => ['Algodón', 'Poliéster', 'Cuero', 'Nylon', 'Lana'],
            'Marca' => ['Nike', 'Adidas', 'Puma', 'Reebok', 'Under Armour'],
            'Estilo' => ['Casual', 'Deportivo', 'Formal', 'Elegante', 'Clásico']
        ];

        foreach ($attributes as $attributeName => $values) {
            $attribute = Attribute::create(['attribute_name' => $attributeName]);

            foreach ($values as $value) {
                $attribute->values()->create(['value_name' => $value]);
            }
        }
    }
}
