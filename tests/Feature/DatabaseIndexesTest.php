<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('indexes the columns used to filter, join or sort', function (string $table, array $columns) {
    $indexedColumns = collect(Schema::getIndexes($table))->map(fn (array $index): string => $index['columns'][0])->all();

    expect(array_diff($columns, $indexedColumns))->toBe([]);
})->with([
    'tasks' => ['tasks', ['task_type_id', 'task_status_id', 'user_id', 'starts_at']],
    'movements' => ['movements', ['movement_type_id', 'from_inventory_position_id', 'to_inventory_position_id', 'date']],
    'movement items' => ['movement_items', ['movement_id', 'inventory_id', 'incoming_stock_id', 'outcoming_stock_id']],
    'inventories' => ['inventories', ['product_id']],
    'stocks' => ['stocks', ['product_id', 'inventory_id', 'inventory_position_id']],
    'products' => ['products', ['product_group_id', 'product_type_id']],
]);
