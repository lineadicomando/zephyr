<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\InventoryPosition;
use App\Models\Scope;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Stock> */
class StockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scope_id' => Scope::factory(),
            // The inventory and the position belong to the scope of the stock.
            'inventory_id' => fn (array $attributes) => Inventory::factory()->state(['scope_id' => $attributes['scope_id']]),
            'inventory_position_id' => fn (array $attributes) => InventoryPosition::factory()->state(['scope_id' => $attributes['scope_id']]),
            'stock' => 1,
        ];
    }
}
