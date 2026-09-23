<?php

namespace Database\Factories;

use App\Models\Scope;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scope_id' => Scope::factory(),
            'name' => fake()->randomElement([
                'Warehouse', 'Server Room', 'Office 1st Floor',
                'Office 2nd Floor', 'IT Storage', 'Reception',
                'Conference Room A', 'Data Center',
            ]).' '.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
