<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Merge stock rows sharing the same scope, inventory and position into the
     * oldest one, so that the unique index added by the next migration can be
     * created. References are moved to the kept row and its quantity is
     * recomputed from the movement items.
     */
    public function up(): void
    {
        $groups = DB::table('stocks')
            ->select('scope_id', 'inventory_id', 'inventory_position_id', DB::raw('MIN(id) as keeper_id'))
            ->whereNotNull('inventory_position_id')
            ->groupBy('scope_id', 'inventory_id', 'inventory_position_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::transaction(function () use ($group): void {
                $keeperId = (int) $group->keeper_id;

                $duplicateIds = DB::table('stocks')
                    ->where('scope_id', $group->scope_id)
                    ->where('inventory_id', $group->inventory_id)
                    ->where('inventory_position_id', $group->inventory_position_id)
                    ->where('id', '!=', $keeperId)
                    ->pluck('id');

                DB::table('movement_items')->whereIn('incoming_stock_id', $duplicateIds)->update(['incoming_stock_id' => $keeperId]);
                DB::table('movement_items')->whereIn('outcoming_stock_id', $duplicateIds)->update(['outcoming_stock_id' => $keeperId]);

                $this->mergeReorders($keeperId, $duplicateIds->all());
                $this->mergeReorderOrderItems($keeperId, $duplicateIds->all());

                DB::table('stocks')->whereIn('id', $duplicateIds)->delete();

                $incoming = (int) DB::table('movement_items')->where('incoming_stock_id', $keeperId)->sum('stock');
                $outcoming = (int) DB::table('movement_items')->where('outcoming_stock_id', $keeperId)->sum('stock');

                DB::table('stocks')->where('id', $keeperId)->update(['stock' => $incoming - $outcoming]);
            });
        }
    }

    /**
     * Intentionally irreversible: merged rows cannot be split again.
     */
    public function down(): void
    {
        //
    }

    /**
     * @param  array<int, int>  $duplicateIds
     */
    protected function mergeReorders(int $keeperId, array $duplicateIds): void
    {
        $keeperReorderId = DB::table('reorders')->where('stock_id', $keeperId)->value('id');

        foreach (DB::table('reorders')->whereIn('stock_id', $duplicateIds)->orderBy('id')->pluck('id') as $reorderId) {
            if ($keeperReorderId === null) {
                DB::table('reorders')->where('id', $reorderId)->update(['stock_id' => $keeperId]);
                $keeperReorderId = $reorderId;

                continue;
            }

            DB::table('reorder_order_items')->where('reorder_id', $reorderId)->update(['reorder_id' => $keeperReorderId]);
            DB::table('reorders')->where('id', $reorderId)->delete();
        }
    }

    /**
     * @param  array<int, int>  $duplicateIds
     */
    protected function mergeReorderOrderItems(int $keeperId, array $duplicateIds): void
    {
        foreach (DB::table('reorder_order_items')->whereIn('stock_id', $duplicateIds)->orderBy('id')->get() as $item) {
            $keeperItemExists = DB::table('reorder_order_items')
                ->where('reorder_order_id', $item->reorder_order_id)
                ->where('stock_id', $keeperId)
                ->exists();

            if ($keeperItemExists) {
                DB::table('reorder_order_items')->where('id', $item->id)->delete();

                continue;
            }

            DB::table('reorder_order_items')->where('id', $item->id)->update(['stock_id' => $keeperId]);
        }
    }
};
