<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Laptop', 'Desktop', 'Switch', 'Router',
                'Monitor', 'Access Point', 'Server', 'Printer',
                'Keyboard', 'Mouse', 'UPS', 'NAS',
            ]),
        ];
    }
}
