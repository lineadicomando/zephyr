<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\EditInventory;
use App\Filament\Resources\InventoryResource\Pages\ListInventories;
use App\Filament\Resources\InventoryResource\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\InventoryResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\MovementItemResource;
use App\Filament\Resources\MovementResource;
use App\Filament\Resources\MovementResource\Pages\CreateMovement;
use App\Filament\Resources\MovementTypeResource;
use App\Filament\Resources\MovementTypeResource\Pages\ListMovementTypes;
use App\Filament\Resources\ProductGroupResource\Pages\ListProductGroups;
use App\Filament\Resources\ReorderOrderResource;
use App\Filament\Resources\ReorderOrderResource\Pages\EditReorderOrder;
use App\Filament\Resources\ReorderOrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\ReorderResource;
use App\Filament\Resources\ReorderResource\Pages\CreateReorder;
use App\Filament\Resources\StockResource;
use App\Filament\Resources\StockResource\Pages\ListStocks;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\ProductGroup;
use App\Models\Reorder;
use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\Stock;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);

    $this->user = User::factory()->create();
    $this->user->syncRoles(['admin']);
    $this->user->scopes()->attach($this->scope);
    $this->actingAs($this->user);

    activateFilamentTenant($this->scope, [
        InventoryResource::class,
        MovementResource::class,
        MovementItemResource::class,
        MovementTypeResource::class,
        ReorderResource::class,
        ReorderOrderResource::class,
        StockResource::class,
        TaskResource::class,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('proposes the current time in 24 hour format for new movements', function () {
    Carbon::setTestNow('2026-05-03 15:30:00');

    Livewire::test(CreateMovement::class)
        ->assertSchemaStateSet(['date' => '2026-05-03 15:30']);
});

it('links inventory movements to the movement page of the current tenant', function () {
    $location = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);
    $position = InventoryPosition::factory()->create(['scope_id' => $this->scope->id, 'inventory_location_id' => $location->id]);
    $inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $movement = Movement::factory()->create([
        'scope_id' => $this->scope->id,
        'to_inventory_location_id' => $location->id,
        'to_inventory_position_id' => $position->id,
    ]);
    $item = MovementItem::query()->create([
        'scope_id' => $this->scope->id,
        'movement_id' => $movement->id,
        'inventory_id' => $inventory->id,
        'stock' => 1,
    ]);

    Livewire::test(MovementsRelationManager::class, ['ownerRecord' => $inventory, 'pageClass' => EditInventory::class])
        ->assertActionHasUrl(
            TestAction::make('Movement')->table($item),
            MovementResource::getUrl('view', ['record' => $movement->id]),
        );

    expect(MovementResource::getUrl('view', ['record' => $movement->id]))->toContain("/{$this->scope->slug}/");
});

it('detaches tasks from an inventory in bulk without deleting them', function () {
    $inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $task = Task::factory()->create(['scope_id' => $this->scope->id, 'user_id' => $this->user->id]);
    $inventory->tasks()->attach($task);

    Livewire::test(TasksRelationManager::class, ['ownerRecord' => $inventory, 'pageClass' => EditInventory::class])
        ->selectTableRecords([$task->id])
        ->callAction(TestAction::make(DetachBulkAction::class)->table()->bulk());

    expect($task->fresh())->not->toBeNull()
        ->and($inventory->tasks()->count())->toBe(0);
});

it('validates email and password when creating users', function () {
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'New user'])
        ->call('create')
        ->assertHasFormErrors(['email' => 'required', 'password' => 'required']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Duplicate',
            'email' => $this->user->email,
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

it('rejects a second reorder rule for the same stock', function () {
    $stock = Stock::factory()->create(['scope_id' => $this->scope->id]);
    Reorder::factory()->create(['scope_id' => $this->scope->id, 'stock_id' => $stock->id]);

    Livewire::test(CreateReorder::class)
        ->fillForm([
            'stock_id' => $stock->id,
            'reorder_point' => 2,
        ])
        ->call('create')
        ->assertHasFormErrors(['stock_id' => 'unique']);
});

it('does not let users without update permission toggle the movement type chart', function () {
    $viewer = User::factory()->create();
    $viewer->syncRoles([]);
    $viewer->givePermissionTo(['ViewAny:MovementType', 'View:MovementType']);
    $viewer->scopes()->attach($this->scope);

    $type = MovementType::factory()->create(['scope_id' => $this->scope->id, 'chart' => false, 'chart_color' => '#ffffff']);

    $this->actingAs($viewer);

    Livewire::test(ListMovementTypes::class)
        ->call('updateTableColumnState', 'chart', (string) $type->getKey(), true);

    expect((bool) $type->fresh()->chart)->toBeFalse();
});

it('edits the ordered quantity of reorder order items', function () {
    $order = ReorderOrder::query()->create(['scope_id' => $this->scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);
    $stock = Stock::factory()->create(['scope_id' => $this->scope->id]);
    $item = $order->items()->create([
        'scope_id' => $this->scope->id,
        'stock_id' => $stock->id,
        'current_stock' => 0,
        'reorder_point' => 2,
        'suggested_qty' => 3,
    ]);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => EditReorderOrder::class])
        ->callAction(TestAction::make(EditAction::class)->table($item), ['ordered_qty' => 7])
        ->assertHasNoFormErrors();

    expect($item->fresh()->ordered_qty)->toBe(7);
});

it('routes tenant slugs ending with api to the panel and api paths to the api', function () {
    $panelRoute = Route::getRoutes()->match(Request::create('/sapi/inventories'));
    $apiRoute = Route::getRoutes()->match(Request::create('/api/products'));

    expect($panelRoute->getName())->toStartWith('filament.app.')
        ->and($panelRoute->parameter('tenant'))->toBe('sapi')
        ->and($apiRoute->uri())->toBe('api/products');
});

it('restores a deleted product group so its name can be used again', function () {
    $root = User::factory()->create();
    $root->assignRole('super_admin');
    $this->actingAs($root);

    $group = ProductGroup::query()->create(['name' => 'Laptop']);
    $group->delete();

    Livewire::test(ListProductGroups::class)
        ->filterTable('trashed', true)
        ->assertCanSeeTableRecords([$group])
        ->callAction(TestAction::make('restore')->table($group));

    expect($group->fresh()->trashed())->toBeFalse();
});

it('filters inventories and stocks by the location of their stock', function () {
    $warehouse = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);
    $office = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);
    $stockAt = fn (InventoryLocation $location): Stock => Stock::factory()->create([
        'scope_id' => $this->scope->id,
        'inventory_id' => Inventory::factory()->create(['scope_id' => $this->scope->id])->id,
        'inventory_position_id' => InventoryPosition::factory()->create(['scope_id' => $this->scope->id, 'inventory_location_id' => $location->id])->id,
        'stock' => 1,
    ]);
    $warehouseStock = $stockAt($warehouse);
    $officeStock = $stockAt($office);

    Livewire::test(ListInventories::class)
        ->filterTable('location', $warehouse->id)
        ->assertCanSeeTableRecords([$warehouseStock->inventory])
        ->assertCanNotSeeTableRecords([$officeStock->inventory]);

    Livewire::test(ListStocks::class)
        ->filterTable('location', $office->id)
        ->assertCanSeeTableRecords([$officeStock])
        ->assertCanNotSeeTableRecords([$warehouseStock]);
});

it('fills the movement data in the edit form of the inventory movements tab', function () {
    $location = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);
    $position = InventoryPosition::factory()->create(['scope_id' => $this->scope->id, 'inventory_location_id' => $location->id]);
    $inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $movement = Movement::factory()->create([
        'scope_id' => $this->scope->id,
        'to_inventory_position_id' => $position->id,
        'description' => 'Delivery to the lab',
        'note' => 'Signed by Anna',
    ]);
    $item = MovementItem::query()->create([
        'scope_id' => $this->scope->id,
        'movement_id' => $movement->id,
        'inventory_id' => $inventory->id,
        'stock' => 3,
    ]);

    Livewire::test(MovementsRelationManager::class, ['ownerRecord' => $inventory, 'pageClass' => EditInventory::class])
        ->mountAction(TestAction::make('edit')->table($item))
        ->assertSchemaStateSet([
            'description' => 'Delivery to the lab',
            'note' => 'Signed by Anna',
            'movement_type_id' => $movement->movement_type_id,
            'to_inventory_position_id' => $position->id,
            'stock' => 3,
        ]);
});
