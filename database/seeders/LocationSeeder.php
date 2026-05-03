<?php

namespace Database\Seeders;

use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use Illuminate\Database\Seeder;
use RuntimeException;

class LocationSeeder extends Seeder
{
    public function __construct(
        private readonly ?int $scopeId = null,
    ) {
    }

    public function run(): void
    {
        if (! is_int($this->scopeId)) {
            throw new RuntimeException('LocationSeeder requires a scope id.');
        }

        $locations = [
            "Warehouse" => ["Shelf A", "Shelf B", "Rack 1", "Rack 2"],
            "Server Room" => ["Rack A", "Rack B", "Rack C"],
            "Office 1st Floor" => [
                "Desk Area",
                "Meeting Room",
                "Storage Cabinet",
            ],
            "Office 2nd Floor" => [
                "Desk Area",
                "Meeting Room",
                "Storage Cabinet",
            ],
            "IT Storage" => ["Cabinet 1", "Cabinet 2", "Shelf 1"],
        ];

        foreach ($locations as $locationName => $positions) {
            // InventoryLocation auto-creates a "default" position on save
            $location = InventoryLocation::create([
                "scope_id" => $this->scopeId,
                "name" => $locationName,
            ]);

            foreach ($positions as $positionName) {
                InventoryPosition::create([
                    "scope_id" => $this->scopeId,
                    "inventory_location_id" => $location->id,
                    "name" => $positionName,
                    "default" => false,
                ]);
            }
        }
    }
}
