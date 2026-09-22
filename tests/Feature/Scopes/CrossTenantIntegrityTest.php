<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\CreateInventory;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\StockResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Scope;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scopeA = Scope::factory()->create(['is_active' => true]);
    $this->scopeB = Scope::factory()->create(['is_active' => true]);

    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->user->scopes()->attach($this->scopeA);
    $this->actingAs($this->user);
});

/**
 * Activate tenant A. Records created afterwards are associated with it by
 * Filament, so fixtures of other tenants must be created before.
 */
function actInTenantA(object $test): void
{
    activateFilamentTenant($test->scopeA, [InventoryResource::class, StockResource::class]);
}

it('detects products used only by inventories of another tenant', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create(['scope_id' => $this->scopeB->id, 'product_id' => $product->id]);
    actInTenantA($this);

    expect(Inventory::query()->count())->toBe(0)
        ->and($product->hasRelated())->toBeTrue()
        ->and($product->delete())->toBeFalse()
        ->and($product->fresh())->not->toBeNull();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    Livewire::test(ListProducts::class)
        ->selectTableRecords([$product->id])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    expect($product->fresh())->not->toBeNull();
});

it('lets unused products be deleted', function () {
    $product = Product::factory()->create();
    actInTenantA($this);

    Livewire::test(ListProducts::class)
        ->selectTableRecords([$product->id])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    expect($product->fresh())->toBeNull();
});

it('detects catalog records used by stocks of another tenant', function () {
    $type = ProductType::factory()->create();
    $inventory = Inventory::factory()->create(['scope_id' => $this->scopeB->id]);

    Stock::query()->create([
        'scope_id' => $this->scopeB->id,
        'inventory_id' => $inventory->id,
        'stock' => 0,
    ])->forceFill(['product_type_id' => $type->id])->saveQuietly();
    actInTenantA($this);

    expect(Stock::query()->count())->toBe(0)
        ->and($type->hasRelated())->toBeTrue();
});

it('keeps the deletion failure message out of the model attributes', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create(['scope_id' => $this->scopeA->id, 'product_id' => $product->id]);
    actInTenantA($this);

    expect($product->delete())->toBeFalse()
        ->and($product->failureState)->not->toBeNull()
        ->and($product->getAttributes())->not->toHaveKey('failureState');

    $product->name = 'Renamed';

    expect($product->save())->toBeTrue();
});

it('allows the same serial number in different tenants', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'scope_id' => $this->scopeB->id,
        'product_id' => $product->id,
        'serial_number' => 'SN-SHARED',
    ]);
    actInTenantA($this);

    Livewire::test(CreateInventory::class)
        ->fillForm([
            'serial_number' => 'SN-SHARED',
            'product_id' => $product->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Inventory::query()->where('serial_number', 'SN-SHARED')->count())->toBe(1)
        ->and(Inventory::withoutGlobalScopes()->where('serial_number', 'SN-SHARED')->count())->toBe(2);
});

it('assigns the first free inventory number of the tenant', function () {
    actInTenantA($this);
    $first = Inventory::factory()->create(['scope_id' => $this->scopeA->id]);
    $collidingNumber = str_pad((string) ($first->id + 2), 6, '0', STR_PAD_LEFT);

    Inventory::factory()->create([
        'scope_id' => $this->scopeA->id,
        'inventory_number' => $collidingNumber,
    ]);

    $auto = Inventory::factory()->create(['scope_id' => $this->scopeA->id]);

    expect($auto->id)->toBe($first->id + 2)
        ->and($auto->fresh()->inventory_number)->toBe(str_pad((string) ($first->id + 3), 6, '0', STR_PAD_LEFT));
});

it('does not consider inventory numbers of other tenants as taken', function () {
    $first = Inventory::factory()->create(['scope_id' => $this->scopeA->id]);

    Inventory::factory()->create([
        'scope_id' => $this->scopeB->id,
        'inventory_number' => str_pad((string) ($first->id + 2), 6, '0', STR_PAD_LEFT),
    ]);
    actInTenantA($this);

    $auto = Inventory::factory()->create(['scope_id' => $this->scopeA->id]);

    expect($auto->fresh()->inventory_number)->toBe(str_pad((string) $auto->id, 6, '0', STR_PAD_LEFT));
});
