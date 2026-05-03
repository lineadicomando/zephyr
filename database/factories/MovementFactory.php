<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\MovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovementFactory extends Factory
{
    public function definition(): array
    {
        $toLocation = InventoryLocation::factory()->create();

        return [
            'date'                       => fake()->dateTimeBetween('-6 months', 'now'),
            'movement_type_id'           => MovementType::factory(),
            'from_inventory_location_id' => null,
            'from_inventory_position_id' => null,
            'to_inventory_location_id'   => $toLocation->id,
            'to_inventory_position_id'   => InventoryPosition::factory()->for($toLocation, 'inventoryLocation'),
            'description'                => fake()->optional(0.7)->sentence(5),
            'note'                       => fake()->optional(0.2)->sentence(),
        ];
    }

    public function transfer(InventoryLocation $from, InventoryLocation $to): static
    {
        return $this->state(fn () => [
            'from_inventory_location_id' => $from->id,
            'from_inventory_position_id' => $from->inventoryPositions()->inRandomOrder()->first()?->id,
            'to_inventory_location_id'   => $to->id,
            'to_inventory_position_id'   => $to->inventoryPositions()->inRandomOrder()->first()?->id,
        ]);
    }
}
