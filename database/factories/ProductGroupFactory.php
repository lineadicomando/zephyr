<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Computers', 'Network Equipment', 'Displays',
                'Peripherals', 'Servers', 'Power & UPS', 'Storage',
            ]),
        ];
    }
}
