<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\InventoryPosition;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Stock> */
class StockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_id'          => Inventory::factory(),
            'inventory_position_id' => InventoryPosition::factory(),
            'stock'                 => 1,
        ];
    }
}
