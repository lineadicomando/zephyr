<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\EditInventory;
use App\Filament\Resources\InventoryResource\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\MovementItemResource;
use App\Filament\Resources\MovementResource;
use App\Filament\Resources\MovementResource\Pages\EditMovement;
use App\Filament\Resources\MovementResource\Pages\ListMovements;
use App\Filament\Resources\MovementResource\RelationManagers\MovementItemsRelationManager;
use App\Filament\Resources\ReorderOrderResource;
use App\Filament\Resources\ReorderOrderResource\Pages\ListReorderOrders;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);

    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->user->scopes()->attach($this->scope);
    $this->actingAs($this->user);

    activateFilamentTenant($this->scope, [
        InventoryResource::class,
        MovementResource::class,
        MovementItemResource::class,
        ReorderOrderResource::class,
    ]);

    $locationA = InventoryLocation::factory()->create(['scope_id' => $this->scope->id, 'name' => 'Location A']);
    $locationB = InventoryLocation::factory()->create(['scope_id' => $this->scope->id, 'name' => 'Location B']);

    $this->positionA = InventoryPosition::factory()->create([
        'scope_id' => $this->scope->id,
        'inventory_location_id' => $locationA->id,
        'name' => 'Position A',
    ]);
    $this->positionB = InventoryPosition::factory()->create([
        'scope_id' => $this->scope->id,
        'inventory_location_id' => $locationB->id,
        'name' => 'Position B',
    ]);

    $this->inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);

    $this->movementType = MovementType::factory()->create([
        'scope_id' => $this->scope->id,
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    $this->loadMovement = stockWithdrawalMovement($this, from: null, to: $this->positionA);
    $this->loadItem = MovementItem::query()->create([
        'scope_id' => $this->scope->id,
        'movement_id' => $this->loadMovement->id,
        'inventory_id' => $this->inventory->id,
        'stock' => 5,
    ]);

    $this->transfer = stockWithdrawalMovement($this, from: $this->positionA, to: $this->positionB);
});

function stockWithdrawalMovement(object $test, ?InventoryPosition $from, InventoryPosition $to): Movement
{
    return Movement::factory()->create([
        'scope_id' => $test->scope->id,
        'date' => now(),
        'movement_type_id' => $test->movementType->id,
        'from_inventory_location_id' => $from?->inventory_location_id,
        'from_inventory_position_id' => $from?->id,
        'to_inventory_location_id' => $to->inventory_location_id,
        'to_inventory_position_id' => $to->id,
    ]);
}

function stockAt(Inventory $inventory, InventoryPosition $position): int
{
    return (int) Stock::query()
        ->where('inventory_id', $inventory->id)
        ->where('inventory_position_id', $position->id)
        ->value('stock');
}

function movementItemsManager(Movement $movement): mixed
{
    return Livewire::test(MovementItemsRelationManager::class, [
        'ownerRecord' => $movement,
        'pageClass' => EditMovement::class,
    ]);
}

it('rejects withdrawing more than the available quantity', function () {
    movementItemsManager($this->transfer)
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'inventory_id' => $this->inventory->id,
            'stock' => 6,
        ])
        ->assertHasFormErrors(['stock']);

    expect($this->transfer->movement_items()->count())->toBe(0)
        ->and(stockAt($this->inventory, $this->positionA))->toBe(5);
});

it('rejects non positive quantities', function (int $quantity) {
    movementItemsManager($this->transfer)
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'inventory_id' => $this->inventory->id,
            'stock' => $quantity,
        ])
        ->assertHasFormErrors(['stock' => 'min']);

    expect(stockAt($this->inventory, $this->positionA))->toBe(5);
})->with([0, -3]);

it('moves the available quantity between positions', function () {
    movementItemsManager($this->transfer)
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'inventory_id' => $this->inventory->id,
            'stock' => 5,
        ])
        ->assertHasNoFormErrors();

    expect(stockAt($this->inventory, $this->positionA))->toBe(0)
        ->and(stockAt($this->inventory, $this->positionB))->toBe(5);
});

it('validates the availability when editing the quantity of a movement item', function () {
    $item = MovementItem::query()->create([
        'scope_id' => $this->scope->id,
        'movement_id' => $this->transfer->id,
        'inventory_id' => $this->inventory->id,
        'stock' => 3,
    ]);

    movementItemsManager($this->transfer)
        ->callAction(TestAction::make(EditAction::class)->table($item), ['stock' => 6])
        ->assertHasFormErrors(['stock']);

    movementItemsManager($this->transfer)
        ->callAction(TestAction::make(EditAction::class)->table($item), ['stock' => 5])
        ->assertHasNoFormErrors();

    expect($item->fresh()->stock)->toBe(5)
        ->and(stockAt($this->inventory, $this->positionA))->toBe(0);
});

it('validates the quantity of movements created from the inventory page', function (int $quantity) {
    Livewire::test(MovementsRelationManager::class, [
        'ownerRecord' => $this->inventory,
        'pageClass' => EditInventory::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'date' => now()->format('Y-m-d H:i'),
            'movement_type_id' => $this->movementType->id,
            'from_inventory_position_id' => $this->positionA->id,
            'to_inventory_position_id' => $this->positionB->id,
            'stock' => $quantity,
            'description' => 'Transfer',
        ])
        ->assertHasFormErrors(['stock']);

    expect(stockAt($this->inventory, $this->positionA))->toBe(5);
})->with([
    'negative' => -5,
    'over availability' => 6,
]);

it('always creates movements from the inventory page for the owner inventory', function () {
    $otherInventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);

    Livewire::test(MovementsRelationManager::class, [
        'ownerRecord' => $this->inventory,
        'pageClass' => EditInventory::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'inventory_id' => $otherInventory->id,
            'date' => now()->format('Y-m-d H:i'),
            'movement_type_id' => $this->movementType->id,
            'from_inventory_position_id' => $this->positionA->id,
            'to_inventory_position_id' => $this->positionB->id,
            'stock' => 2,
            'description' => 'Transfer',
        ])
        ->assertHasNoFormErrors();

    expect(MovementItem::query()->where('inventory_id', $otherInventory->id)->exists())->toBeFalse()
        ->and(stockAt($this->inventory, $this->positionA))->toBe(3)
        ->and(stockAt($this->inventory, $this->positionB))->toBe(2);
});

it('does not change the inventory of a movement item edited from the inventory page', function () {
    $otherInventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);

    Livewire::test(MovementsRelationManager::class, [
        'ownerRecord' => $this->inventory,
        'pageClass' => EditInventory::class,
    ])
        ->callAction(TestAction::make(EditAction::class)->table($this->loadItem), [
            'inventory_id' => $otherInventory->id,
            'description' => 'Updated description',
        ])
        ->assertHasNoFormErrors();

    expect($this->loadItem->fresh()->inventory_id)->toBe($this->inventory->id)
        ->and($this->loadMovement->fresh()->description)->toBe('Updated description');
});

it('skips movement items that are not the last one in bulk deletion', function () {
    $this->user->syncRoles('super_admin');

    MovementItem::query()->create([
        'scope_id' => $this->scope->id,
        'movement_id' => $this->transfer->id,
        'inventory_id' => $this->inventory->id,
        'stock' => 2,
    ]);

    movementItemsManager($this->loadMovement)
        ->selectTableRecords([$this->loadItem->id])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    expect($this->loadItem->fresh())->not->toBeNull()
        ->and(stockAt($this->inventory, $this->positionA))->toBe(3);
});

it('does not delete movements whose inventories were moved afterwards', function () {
    $this->user->syncRoles('super_admin');

    MovementItem::query()->create([
        'scope_id' => $this->scope->id,
        'movement_id' => $this->transfer->id,
        'inventory_id' => $this->inventory->id,
        'stock' => 2,
    ]);

    expect($this->loadMovement->hasSubsequentMovements())->toBeTrue()
        ->and($this->transfer->hasSubsequentMovements())->toBeFalse();

    Livewire::test(ListMovements::class)
        ->selectTableRecords([$this->loadMovement->id])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    Livewire::test(EditMovement::class, ['record' => $this->loadMovement->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    expect($this->loadMovement->fresh())->not->toBeNull();
});

it('deletes only draft reorder orders in bulk', function () {
    $this->user->syncRoles('super_admin');

    $draft = ReorderOrder::query()->create(['scope_id' => $this->scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);
    $received = ReorderOrder::query()->create(['scope_id' => $this->scope->id, 'status' => ReorderOrder::STATUS_RECEIVED]);

    Livewire::test(ListReorderOrders::class)
        ->selectTableRecords([$draft->id, $received->id])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    expect($draft->fresh())->toBeNull()
        ->and($received->fresh())->not->toBeNull();
});
