<?php

namespace Database\Factories;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAttributeValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            // 'attribute_id' => ProductAttribute::factory(),
            'value_name' => $this->faker->word()
        ];
    }
}
