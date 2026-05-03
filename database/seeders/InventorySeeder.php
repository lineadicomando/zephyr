<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryPosition;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use RuntimeException;

class InventorySeeder extends Seeder
{
    public function __construct(
        private readonly ?int $scopeId = null,
    ) {
    }

    public function run(): void
    {
        if (! is_int($this->scopeId)) {
            throw new RuntimeException('InventorySeeder requires a scope id.');
        }

        $products = Product::all();
        $positions = InventoryPosition::with("inventory_location")
            ->where("scope_id", $this->scopeId)
            ->get();
        $warehouse = $positions->filter(
            fn($p) => str_contains($p->inventory_location->name, "Warehouse"),
        );
        $other = $positions->filter(
            fn($p) => !str_contains($p->inventory_location->name, "Warehouse"),
        );

        // 60 physical items — 70% in warehouse, 30% deployed elsewhere
        $total = 60;
        $inWarehouse = (int) ($total * 0.7);
        $deployed = $total - $inWarehouse;

        foreach (range(1, $inWarehouse) as $_) {
            $inventory = Inventory::create([
                "scope_id" => $this->scopeId,
                "product_id" => $products->random()->id,
                "serial_number" => $this->uniqueSerial(),
                "mac_address" => fake()->optional(0.25)->macAddress(),
                "description" => fake()->optional(0.5)->sentence(4),
            ]);

            Stock::create([
                "scope_id" => $this->scopeId,
                "inventory_id" => $inventory->id,
                "inventory_position_id" => $warehouse->random()->id,
                "stock" => 1,
            ]);
        }

        foreach (range(1, $deployed) as $_) {
            $inventory = Inventory::create([
                "scope_id" => $this->scopeId,
                "product_id" => $products->random()->id,
                "serial_number" => $this->uniqueSerial(),
                "mac_address" => fake()->optional(0.35)->macAddress(),
                "description" => fake()->optional(0.6)->sentence(4),
            ]);

            Stock::create([
                "scope_id" => $this->scopeId,
                "inventory_id" => $inventory->id,
                "inventory_position_id" => $other->random()->id,
                "stock" => 1,
            ]);
        }
    }

    private array $usedSerials = [];

    private function uniqueSerial(): ?string
    {
        if (fake()->boolean(15)) {
            return null;
        }

        do {
            $serial = strtoupper(fake()->bothify("SN-####-???-####"));
        } while (in_array($serial, $this->usedSerials, true));

        $this->usedSerials[] = $serial;

        return $serial;
    }
}
