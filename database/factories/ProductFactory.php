<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Store;
use App\Models\Category;
use App\Models\MeasurementUnit;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'store_id' => null, // Removemos Store::factory()
            'category_id' => null, // Se asignará en el seeder
            'brand_id' => null, // Se asignará en el seeder
            'measurement_unit_id' => null, // Se asignará en el seeder
            'product_name' => $name,
            'slug' => Str::slug($name),
            'price' => $this->faker->numberBetween(1000, 100000),
            'stock' => $this->faker->randomFloat(2, 0, 1000),
            'sku' => $this->faker->unique()->ean8(),
            'status' => $this->faker->boolean(90),
            'description' => $this->faker->paragraph(),
            'offer_price' => $this->faker->optional()->numberBetween(500, 90000),
            'supplier_code' => $this->faker->optional()->bothify('SUP-####'),
            'barcode' => $this->faker->ean13(),
            'weight' => $this->faker->randomFloat(2, 0.1, 100),
            'is_taxable' => $this->faker->boolean(),
            'tax_rate' => $this->faker->randomFloat(2, 0, 19),
            'minimum_stock' => $this->faker->numberBetween(1, 10),
            'maximum_stock' => $this->faker->numberBetween(100, 1000),
        ];
    }
}
