<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Warehouse', 'Server Room', 'Office 1st Floor',
                'Office 2nd Floor', 'IT Storage', 'Reception',
                'Conference Room A', 'Data Center',
            ]),
        ];
    }
}
