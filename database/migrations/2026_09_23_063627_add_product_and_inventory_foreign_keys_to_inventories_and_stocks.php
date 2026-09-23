<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product columns of the stocks, copied from the product of the inventory,
     * and the table they reference.
     *
     * @var array<string, string>
     */
    private const STOCK_PRODUCT_COLUMNS = [
        'product_group_id' => 'product_groups',
        'product_type_id' => 'product_types',
        'product_brand_id' => 'product_brands',
        'product_model_id' => 'product_models',
        'product_id' => 'products',
    ];

    /**
     * Add the missing foreign keys of inventories and stocks. Orphan rows are
     * cleaned up first: inventories of missing products and stocks of missing
     * inventories are deleted with their dependent rows, product columns of
     * the stocks pointing to missing records are emptied (`db:check` copies
     * them again from the product of the inventory).
     */
    public function up(): void
    {
        $deleted = $this->deleteOrphanRows();

        if (array_sum($deleted) > 0) {
            Log::warning('Deleted orphan rows before adding the inventory and stock foreign keys.', $deleted);
        }

        Schema::table('inventories', function (Blueprint $table): void {
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::table('stocks', function (Blueprint $table): void {
            $table->foreign('inventory_id')->references('id')->on('inventories');

            foreach (self::STOCK_PRODUCT_COLUMNS as $column => $referencedTable) {
                $table->foreign($column)->references('id')->on($referencedTable);
            }
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table): void {
            $table->dropForeign(['inventory_id']);

            foreach (array_keys(self::STOCK_PRODUCT_COLUMNS) as $column) {
                $table->dropForeign([$column]);
            }
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
        });
    }

    /**
     * @return array<string, int>
     */
    private function deleteOrphanRows(): array
    {
        $deleted = [];

        $orphanInventoryIds = DB::table('inventories')
            ->whereNotIn('product_id', DB::table('products')->select('id'))
            ->pluck('id');

        $deleted['task_inventory'] = DB::table('task_inventory')->whereIn('inventory_id', $orphanInventoryIds)->delete();
        $deleted['movement_items'] = DB::table('movement_items')->whereIn('inventory_id', $orphanInventoryIds)->delete();

        $orphanStockIds = DB::table('stocks')
            ->whereIn('inventory_id', $orphanInventoryIds)
            ->orWhereNotIn('inventory_id', DB::table('inventories')->select('id'))
            ->pluck('id');

        $deleted['reorder_order_items'] = DB::table('reorder_order_items')->whereIn('stock_id', $orphanStockIds)->delete();
        $deleted['reorders'] = DB::table('reorders')->whereIn('stock_id', $orphanStockIds)->delete();
        DB::table('movement_items')->whereIn('incoming_stock_id', $orphanStockIds)->update(['incoming_stock_id' => null]);
        DB::table('movement_items')->whereIn('outcoming_stock_id', $orphanStockIds)->update(['outcoming_stock_id' => null]);
        $deleted['stocks'] = DB::table('stocks')->whereIn('id', $orphanStockIds)->delete();
        $deleted['inventories'] = DB::table('inventories')->whereIn('id', $orphanInventoryIds)->delete();

        foreach (self::STOCK_PRODUCT_COLUMNS as $column => $referencedTable) {
            $deleted["stocks.{$column} emptied"] = DB::table('stocks')
                ->whereNotNull($column)
                ->whereNotIn($column, DB::table($referencedTable)->select('id'))
                ->update([$column => null]);
        }

        return $deleted;
    }
};
