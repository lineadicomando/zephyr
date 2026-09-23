<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\MovementType;
use App\Models\Scope;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scope_id' => Scope::factory(),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            // The type and the destination belong to the scope of the movement.
            'movement_type_id' => fn (array $attributes) => MovementType::factory()->state(['scope_id' => $attributes['scope_id']]),
            'from_inventory_location_id' => null,
            'from_inventory_position_id' => null,
            'to_inventory_position_id' => fn (array $attributes) => InventoryPosition::factory()->state(['scope_id' => $attributes['scope_id']]),
            'to_inventory_location_id' => fn (array $attributes) => InventoryPosition::query()->whereKey($attributes['to_inventory_position_id'])->value('inventory_location_id'),
            'description' => fake()->optional(0.7)->sentence(5),
            'note' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function transfer(InventoryLocation $from, InventoryLocation $to): static
    {
        return $this->state(fn () => [
            'from_inventory_location_id' => $from->id,
            'from_inventory_position_id' => $from->inventory_positions()->inRandomOrder()->first()?->id,
            'to_inventory_location_id' => $to->id,
            'to_inventory_position_id' => $to->inventory_positions()->inRandomOrder()->first()?->id,
        ]);
    }
}
