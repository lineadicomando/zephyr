<?php

namespace Database\Factories;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReorderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stock_id'         => Stock::factory(),
            'reorder_point'    => fake()->numberBetween(1, 5),
            'reorder_quantity'  => fake()->optional(0.8)->numberBetween(2, 20),
            'last_reorder_date' => fake()->optional(0.5)->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
