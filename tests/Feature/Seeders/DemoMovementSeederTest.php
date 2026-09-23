<?php

use App\Models\MovementItem;
use App\Models\Scope;
use App\Models\Stock;
use Database\Seeders\InventorySeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\MovementSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds demo stocks that match their movement history', function () {
    $scope = Scope::factory()->create(['is_active' => true]);

    (new ProductCatalogSeeder)->run();
    (new LocationSeeder($scope->id))->run();
    (new InventorySeeder($scope->id))->run();
    (new MovementSeeder($scope->id))->run();

    $stocks = Stock::query()->where('scope_id', $scope->id)->get();

    expect($stocks)->not->toBeEmpty()
        ->and($stocks->sum('stock'))->toBe(60);

    $stocks->each(function (Stock $stock) {
        $history = MovementItem::query()->where('incoming_stock_id', $stock->id)->sum('stock')
            - MovementItem::query()->where('outcoming_stock_id', $stock->id)->sum('stock');

        expect($stock->stock)->toBe((int) $history)->toBeGreaterThanOrEqual(0);
    });
});
