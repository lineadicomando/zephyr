<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;
use RuntimeException;

class InventorySeeder extends Seeder
{
    public function __construct(
        private readonly ?int $scopeId = null,
    ) {}

    public function run(): void
    {
        if (! is_int($this->scopeId)) {
            throw new RuntimeException('InventorySeeder requires a scope id.');
        }

        $products = Product::all();

        /*
         * 60 physical items without stock: MovementSeeder brings them into
         * the warehouse with purchase movements and deploys part of them, so
         * that every stock matches its movement history.
         */
        foreach (range(1, 60) as $_) {
            Inventory::create([
                'scope_id' => $this->scopeId,
                'product_id' => $products->random()->id,
                'serial_number' => $this->uniqueSerial(),
                'mac_address' => fake()->optional(0.3)->macAddress(),
                'description' => fake()->optional(0.55)->sentence(4),
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
            $serial = strtoupper(fake()->bothify('SN-####-???-####'));
        } while (in_array($serial, $this->usedSerials, true));

        $this->usedSerials[] = $serial;

        return $serial;
    }
}
