<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+2 months');
        $endDate = clone $startDate;
        $endDate->modify('+' . rand(1, 4) . ' hours');

        return [
            'store_id' => Store::inRandomOrder()->first()->id,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_at' => $startDate,
            'end_at' => $endDate,
        ];
    }
}
