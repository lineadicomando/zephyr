<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns used to filter, join or sort, by table.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'tasks' => ['task_type_id', 'task_status_id', 'user_id', 'starts_at'],
        'movements' => ['movement_type_id', 'from_inventory_location_id', 'from_inventory_position_id', 'to_inventory_location_id', 'to_inventory_position_id', 'date'],
        'movement_items' => ['movement_id', 'inventory_id', 'incoming_stock_id', 'outcoming_stock_id'],
        'inventories' => ['product_id'],
        'stocks' => ['product_group_id', 'product_type_id', 'product_brand_id', 'product_model_id', 'product_id', 'inventory_id', 'inventory_location_id', 'inventory_position_id'],
        'products' => ['product_group_id', 'product_type_id', 'product_brand_id', 'product_model_id'],
    ];

    /**
     * Index the columns that no index starts with yet: MySQL and MariaDB
     * already index the foreign key columns, SQLite does not.
     */
    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $indexedColumns = collect(Schema::getIndexes($table))->map(fn (array $index): string => $index['columns'][0]);
            $missingColumns = array_diff($columns, $indexedColumns->all());

            Schema::table($table, function (Blueprint $blueprint) use ($missingColumns): void {
                foreach ($missingColumns as $column) {
                    $blueprint->index($column);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $indexNames = collect(Schema::getIndexes($table))->pluck('name');

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns, $indexNames): void {
                foreach ($columns as $column) {
                    if ($indexNames->contains("{$table}_{$column}_index")) {
                        $blueprint->dropIndex([$column]);
                    }
                }
            });
        }
    }
};
