<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\Scope;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MovementSeeder extends Seeder
{
    public function __construct(
        private readonly ?int $scopeId = null,
    ) {
    }

    public function run(): void
    {
        if (! is_int($this->scopeId)) {
            throw new RuntimeException('MovementSeeder requires a scope id.');
        }

        $types = collect([
            ["name" => "Purchase", "chart" => true, "chart_color" => "#22c55e"],
            ["name" => "Transfer", "chart" => true, "chart_color" => "#3b82f6"],
            ["name" => "Disposal", "chart" => true, "chart_color" => "#ef4444"],
            ["name" => "Return", "chart" => false, "chart_color" => "#f97316"],
            ["name" => "Loan", "chart" => false, "chart_color" => "#a855f7"],
            [
                "name" => "Maintenance",
                "chart" => false,
                "chart_color" => "#eab308",
            ],
        ])->map(fn($data) => MovementType::create([
            "scope_id" => $this->scopeId,
            ...$data,
        ]));

        $purchaseType = $types->firstWhere("name", "Purchase");
        $transferType = $types->firstWhere("name", "Transfer");
        $returnType = $types->firstWhere("name", "Return");

        $warehouse = InventoryLocation::where("scope_id", $this->scopeId)
            ->where("name", "Warehouse")
            ->first();
        $locations = InventoryLocation::where("scope_id", $this->scopeId)
            ->where("name", "!=", "Warehouse")
            ->get();

        if (! $warehouse) {
            throw new RuntimeException('Warehouse location not found for scope in MovementSeeder.');
        }

        // 8 Purchase movements: items arriving at warehouse (no from-location)
        for ($i = 0; $i < 8; $i++) {
            $toPosition =
                $warehouse
                    ->inventory_positions()
                    ->where("default", false)
                    ->inRandomOrder()
                    ->first() ?? $warehouse->inventory_positions()->first();

            $movement = Movement::create([
                "scope_id" => $this->scopeId,
                "date" => fake()->dateTimeBetween("-12 months", "-6 months"),
                "movement_type_id" => $purchaseType->id,
                "to_inventory_location_id" => $warehouse->id,
                "to_inventory_position_id" => $toPosition->id,
                "description" => "Hardware procurement — batch " . ($i + 1),
            ]);

            $stocks = Stock::whereHas(
                "inventory_position",
                fn($q) => $q
                    ->where("scope_id", $this->scopeId)
                    ->where("inventory_location_id", $warehouse->id),
            )
                ->with("inventory")
                ->inRandomOrder()
                ->limit(rand(3, 5))
                ->get();

            foreach ($stocks as $stock) {
                $this->insertMovementItem(
                    $movement->id,
                    $stock->inventory_id,
                    null,
                    $stock->id,
                    $stock->inventory->summary ?? "",
                    1,
                );
            }
        }

        // 10 Transfer movements: items moved from warehouse to offices
        for ($i = 0; $i < 10; $i++) {
            $toLocation = $locations->random();
            $toPosition = $toLocation
                ->inventory_positions()
                ->inRandomOrder()
                ->first();
            $fromPosition =
                $warehouse
                    ->inventory_positions()
                    ->where("default", false)
                    ->inRandomOrder()
                    ->first() ?? $warehouse->inventory_positions()->first();

            if (!$toPosition || !$fromPosition) {
                continue;
            }

            $movement = Movement::create([
                "scope_id" => $this->scopeId,
                "date" => fake()->dateTimeBetween("-5 months", "-1 month"),
                "movement_type_id" => $transferType->id,
                "from_inventory_location_id" => $warehouse->id,
                "from_inventory_position_id" => $fromPosition->id,
                "to_inventory_location_id" => $toLocation->id,
                "to_inventory_position_id" => $toPosition->id,
                "description" => "Deployment to " . $toLocation->name,
            ]);

            $fromStocks = Stock::whereHas(
                "inventory_position",
                fn($q) => $q
                    ->where("scope_id", $this->scopeId)
                    ->where("inventory_location_id", $warehouse->id),
            )
                ->with("inventory")
                ->inRandomOrder()
                ->limit(rand(1, 3))
                ->get();

            foreach ($fromStocks as $fromStock) {
                $toStock = Stock::where(
                    "inventory_id",
                    $fromStock->inventory_id,
                )
                    ->where("inventory_position_id", $toPosition->id)
                    ->first();

                $this->insertMovementItem(
                    $movement->id,
                    $fromStock->inventory_id,
                    $fromStock->id,
                    $toStock?->id,
                    $fromStock->inventory->summary ?? "",
                    1,
                );
            }
        }

        // 2 Return movements: items going back to warehouse
        for ($i = 0; $i < 2; $i++) {
            $fromLocation = $locations->random();
            $fromPosition = $fromLocation
                ->inventory_positions()
                ->inRandomOrder()
                ->first();
            $toPosition =
                $warehouse
                    ->inventory_positions()
                    ->where("default", false)
                    ->inRandomOrder()
                    ->first() ?? $warehouse->inventory_positions()->first();

            $deployedStocks = Stock::whereHas(
                "inventory_position",
                fn($q) => $q
                    ->where("scope_id", $this->scopeId)
                    ->where("inventory_location_id", $fromLocation->id),
            )
                ->with("inventory")
                ->inRandomOrder()
                ->limit(rand(1, 2))
                ->get();

            if ($deployedStocks->isEmpty() || !$fromPosition || !$toPosition) {
                continue;
            }

            $movement = Movement::create([
                "scope_id" => $this->scopeId,
                "date" => fake()->dateTimeBetween("-1 month", "now"),
                "movement_type_id" => $returnType->id,
                "from_inventory_location_id" => $fromLocation->id,
                "from_inventory_position_id" => $fromPosition->id,
                "to_inventory_location_id" => $warehouse->id,
                "to_inventory_position_id" => $toPosition->id,
                "description" => "Return from " . $fromLocation->name,
            ]);

            foreach ($deployedStocks as $deployedStock) {
                $warehouseStock = Stock::where(
                    "inventory_id",
                    $deployedStock->inventory_id,
                )
                    ->whereHas(
                        "inventory_position",
                        fn($q) => $q
                            ->where("scope_id", $this->scopeId)
                            ->where("inventory_location_id", $warehouse->id),
                    )
                    ->first();

                $this->insertMovementItem(
                    $movement->id,
                    $deployedStock->inventory_id,
                    $deployedStock->id,
                    $warehouseStock?->id,
                    $deployedStock->inventory->summary ?? "",
                    1,
                );
            }
        }
    }

    private function insertMovementItem(
        int $movementId,
        int $inventoryId,
        ?int $outcomingStockId,
        ?int $incomingStockId,
        string $summary,
        int $stock,
    ): void {
        // Use DB::table() to bypass syncStocks() which incorrectly sets 0 instead of null
        $now = now();
        $scopeId = DB::table('movements')->where('id', $movementId)->value('scope_id');
        if (! is_numeric($scopeId)) {
            $scopeId = Scope::query()->where('slug', 'default')->value('id');
        }
        if (! is_numeric($scopeId)) {
            throw new \RuntimeException('Missing scope_id for movement item seed. Ensure scopes are seeded first.');
        }

        DB::table("movement_items")->insert([
            "scope_id" => (int) $scopeId,
            "movement_id" => $movementId,
            "inventory_id" => $inventoryId,
            "outcoming_stock_id" => $outcomingStockId,
            "incoming_stock_id" => $incomingStockId,
            "inventory_summary" => $summary,
            "stock" => $stock,
            "created_at" => $now,
            "updated_at" => $now,
        ]);
    }
}
