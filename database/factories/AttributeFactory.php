<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attribute_name' => $this->faker->unique()->word()
        ];
    }
}
