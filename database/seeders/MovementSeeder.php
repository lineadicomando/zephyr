<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use RuntimeException;

class MovementSeeder extends Seeder
{
    public function __construct(
        private readonly ?int $scopeId = null,
    ) {}

    public function run(): void
    {
        if (! is_int($this->scopeId)) {
            throw new RuntimeException('MovementSeeder requires a scope id.');
        }

        $types = collect([
            ['name' => 'Purchase', 'chart' => true, 'chart_color' => '#22c55e'],
            ['name' => 'Transfer', 'chart' => true, 'chart_color' => '#3b82f6'],
            ['name' => 'Disposal', 'chart' => true, 'chart_color' => '#ef4444'],
            ['name' => 'Return', 'chart' => false, 'chart_color' => '#f97316'],
            ['name' => 'Loan', 'chart' => false, 'chart_color' => '#a855f7'],
            [
                'name' => 'Maintenance',
                'chart' => false,
                'chart_color' => '#eab308',
            ],
        ])->map(fn ($data) => MovementType::create([
            'scope_id' => $this->scopeId,
            ...$data,
        ]));

        $purchaseType = $types->firstWhere('name', 'Purchase');
        $transferType = $types->firstWhere('name', 'Transfer');
        $returnType = $types->firstWhere('name', 'Return');

        $warehouse = InventoryLocation::where('scope_id', $this->scopeId)
            ->where('name', 'Warehouse')
            ->first();
        $locations = InventoryLocation::where('scope_id', $this->scopeId)
            ->where('name', '!=', 'Warehouse')
            ->get();

        if (! $warehouse) {
            throw new RuntimeException('Warehouse location not found for scope in MovementSeeder.');
        }

        $warehousePositions = $warehouse->inventory_positions()->where('default', false)->get();
        if ($warehousePositions->isEmpty()) {
            $warehousePositions = $warehouse->inventory_positions()->get();
        }

        // 8 Purchase movements: every item arrives at the warehouse
        $inventoryIds = Inventory::query()->where('scope_id', $this->scopeId)->pluck('id')->shuffle();
        foreach ($inventoryIds->split(8)->values() as $i => $batch) {
            $toPosition = $warehousePositions->random();

            $movement = Movement::create([
                'scope_id' => $this->scopeId,
                'date' => fake()->dateTimeBetween('-12 months', '-6 months'),
                'movement_type_id' => $purchaseType->id,
                'to_inventory_location_id' => $warehouse->id,
                'to_inventory_position_id' => $toPosition->id,
                'description' => 'Hardware procurement — batch '.($i + 1),
            ]);

            $batch->each(fn (int $inventoryId) => $this->addMovementItem($movement, $inventoryId));
        }

        // 10 Transfer movements: items moved from the warehouse to offices
        for ($i = 0; $i < 10; $i++) {
            $toLocation = $locations->random();
            $toPosition = $toLocation->inventory_positions()->inRandomOrder()->first();
            $fromStocks = $this->stocksAtOnePosition($this->availableStocks([$warehouse->id]), rand(1, 3));

            if (! $toPosition || $fromStocks->isEmpty()) {
                continue;
            }

            $movement = Movement::create([
                'scope_id' => $this->scopeId,
                'date' => fake()->dateTimeBetween('-5 months', '-1 month'),
                'movement_type_id' => $transferType->id,
                'from_inventory_location_id' => $warehouse->id,
                'from_inventory_position_id' => $fromStocks->first()->inventory_position_id,
                'to_inventory_location_id' => $toLocation->id,
                'to_inventory_position_id' => $toPosition->id,
                'description' => 'Deployment to '.$toLocation->name,
            ]);

            $fromStocks->each(fn (Stock $stock) => $this->addMovementItem($movement, $stock->inventory_id));
        }

        // 2 Return movements: items going back to the warehouse
        for ($i = 0; $i < 2; $i++) {
            $deployedStocks = $this->stocksAtOnePosition($this->availableStocks($locations->pluck('id')->all()), rand(1, 2));

            if ($deployedStocks->isEmpty()) {
                continue;
            }

            $fromLocation = $deployedStocks->first()->inventory_location;

            $movement = Movement::create([
                'scope_id' => $this->scopeId,
                'date' => fake()->dateTimeBetween('-1 month', 'now'),
                'movement_type_id' => $returnType->id,
                'from_inventory_location_id' => $fromLocation->id,
                'from_inventory_position_id' => $deployedStocks->first()->inventory_position_id,
                'to_inventory_location_id' => $warehouse->id,
                'to_inventory_position_id' => $warehousePositions->random()->id,
                'description' => 'Return from '.$fromLocation->name,
            ]);

            $deployedStocks->each(fn (Stock $stock) => $this->addMovementItem($movement, $stock->inventory_id));
        }
    }

    /**
     * Stocks with availability in the given locations.
     *
     * @param  array<int, int>  $locationIds
     * @return Collection<int, Stock>
     */
    private function availableStocks(array $locationIds): Collection
    {
        return Stock::query()
            ->with('inventory_location')
            ->where('scope_id', $this->scopeId)
            ->whereIn('inventory_location_id', $locationIds)
            ->where('stock', '>', 0)
            ->get();
    }

    /**
     * Up to $count of the stocks lying at one random position: a movement
     * takes its items from a single position.
     *
     * @param  Collection<int, Stock>  $stocks
     * @return Collection<int, Stock>
     */
    private function stocksAtOnePosition(Collection $stocks, int $count): Collection
    {
        if ($stocks->isEmpty()) {
            return $stocks;
        }

        $atPosition = $stocks->where('inventory_position_id', $stocks->random()->inventory_position_id);

        return $atPosition->random(min($count, $atPosition->count()))->values();
    }

    /**
     * Save the item through the model, so the stocks follow the movements.
     */
    private function addMovementItem(Movement $movement, int $inventoryId): void
    {
        MovementItem::create([
            'scope_id' => $this->scopeId,
            'movement_id' => $movement->id,
            'inventory_id' => $inventoryId,
            'stock' => 1,
        ]);
    }
}
