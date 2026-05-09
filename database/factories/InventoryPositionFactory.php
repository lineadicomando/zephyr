<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use App\Models\Scope;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryPositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scope_id' => Scope::factory(),
            'inventory_location_id' => InventoryLocation::factory(),
            'name' => fake()->unique()->randomElement([
                'Shelf A', 'Shelf B', 'Shelf C',
                'Rack 1', 'Rack 2', 'Rack 3',
                'Cabinet 1', 'Cabinet 2',
                'Desk Area', 'Meeting Room',
                'Storage Unit 1', 'Storage Unit 2',
            ]),
            'default' => false,
        ];
    }
}
