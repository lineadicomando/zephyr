<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stocks without a position escaped the (scope, inventory, position)
     * unique index and mixed up every location a movement reached without a
     * position. Movement sides with a location but no position get the
     * default position of the location, the movement items are moved to the
     * stock of their position, the stocks without position are deleted and
     * the column becomes required.
     */
    public function up(): void
    {
        $touchedStockIds = [];

        DB::transaction(function () use (&$touchedStockIds): void {
            foreach (['from', 'to'] as $side) {
                DB::table('movements')
                    ->whereNotNull("{$side}_inventory_location_id")
                    ->whereNull("{$side}_inventory_position_id")
                    ->orderBy('id')
                    ->get(['id', 'scope_id', "{$side}_inventory_location_id as location_id"])
                    ->each(fn (object $movement) => DB::table('movements')->where('id', $movement->id)->update([
                        "{$side}_inventory_position_id" => $this->defaultPositionId((int) $movement->scope_id, (int) $movement->location_id),
                    ]));
            }

            $stockIdsWithoutPosition = DB::table('stocks')->whereNull('inventory_position_id')->pluck('id');

            foreach (['incoming' => 'to', 'outcoming' => 'from'] as $direction => $side) {
                DB::table('movement_items')
                    ->join('movements', 'movements.id', '=', 'movement_items.movement_id')
                    ->whereIn("movement_items.{$direction}_stock_id", $stockIdsWithoutPosition)
                    ->get(['movement_items.id', 'movement_items.scope_id', 'movement_items.inventory_id', "movements.{$side}_inventory_position_id as position_id"])
                    ->each(function (object $item) use ($direction, &$touchedStockIds): void {
                        $stockId = $item->position_id === null
                            ? null
                            : $this->stockId((int) $item->scope_id, (int) $item->inventory_id, (int) $item->position_id);

                        DB::table('movement_items')->where('id', $item->id)->update(["{$direction}_stock_id" => $stockId]);
                        $touchedStockIds[] = $stockId;
                    });
            }

            $deleted = [
                'reorder_order_items' => DB::table('reorder_order_items')->whereIn('stock_id', $stockIdsWithoutPosition)->delete(),
                'reorders' => DB::table('reorders')->whereIn('stock_id', $stockIdsWithoutPosition)->delete(),
                'stocks' => DB::table('stocks')->whereIn('id', $stockIdsWithoutPosition)->delete(),
            ];

            if (array_sum($deleted) > 0) {
                Log::warning('Deleted stocks without position and their reorder rules.', $deleted);
            }

            foreach (array_unique(array_filter($touchedStockIds)) as $stockId) {
                $incoming = (int) DB::table('movement_items')->where('incoming_stock_id', $stockId)->sum('stock');
                $outcoming = (int) DB::table('movement_items')->where('outcoming_stock_id', $stockId)->sum('stock');

                DB::table('stocks')->where('id', $stockId)->update(['stock' => $incoming - $outcoming]);
            }
        });

        Schema::table('stocks', function (Blueprint $table): void {
            $table->unsignedBigInteger('inventory_position_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table): void {
            $table->unsignedBigInteger('inventory_position_id')->nullable()->change();
        });
    }

    /**
     * Default position of the location, created as InventoryLocation::defaultPosition() does.
     */
    private function defaultPositionId(int $scopeId, int $locationId): int
    {
        $positionId = DB::table('inventory_positions')
            ->where('inventory_location_id', $locationId)
            ->where('default', true)
            ->whereNull('deleted_at')
            ->value('id');

        return (int) ($positionId ?? DB::table('inventory_positions')->insertGetId([
            'scope_id' => $scopeId,
            'inventory_location_id' => $locationId,
            'name' => 'default',
            'default' => true,
            'path' => (string) DB::table('inventory_locations')->where('id', $locationId)->value('name'),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /**
     * Stock of the inventory at the position, created with the columns that
     * Stock::onSaving() copies from the inventory and the position.
     */
    private function stockId(int $scopeId, int $inventoryId, int $positionId): int
    {
        $stockId = DB::table('stocks')
            ->where('scope_id', $scopeId)
            ->where('inventory_id', $inventoryId)
            ->where('inventory_position_id', $positionId)
            ->value('id');

        if ($stockId !== null) {
            return (int) $stockId;
        }

        $inventory = DB::table('inventories')->where('id', $inventoryId)->first(['product_id', 'summary']);
        $product = DB::table('products')->where('id', $inventory->product_id)
            ->first(['product_group_id', 'product_type_id', 'product_brand_id', 'product_model_id']);
        $position = DB::table('inventory_positions')->where('id', $positionId)->first(['inventory_location_id', 'path']);

        return (int) DB::table('stocks')->insertGetId([
            'scope_id' => $scopeId,
            'inventory_id' => $inventoryId,
            'inventory_position_id' => $positionId,
            'inventory_location_id' => $position->inventory_location_id,
            'product_id' => $inventory->product_id,
            'product_group_id' => $product?->product_group_id,
            'product_type_id' => $product?->product_type_id,
            'product_brand_id' => $product?->product_brand_id,
            'product_model_id' => $product?->product_model_id,
            'inventory_summary' => $inventory->summary,
            'path' => $position->path.': 0',
            'stock' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
