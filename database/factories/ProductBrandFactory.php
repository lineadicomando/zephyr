<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBrandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'HP', 'Dell', 'Apple', 'Cisco', 'Ubiquiti',
                'Lenovo', 'Logitech', 'Samsung', 'LG', 'APC',
            ]),
        ];
    }
}
