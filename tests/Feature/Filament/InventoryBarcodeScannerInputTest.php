<?php

use App\Filament\Forms\Components\BarcodeScannerInput;
use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\CreateInventory;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

function superAdminForInventoryResource(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

function setActiveScopeForInventory(User $user): Scope
{
    $scope = Scope::factory()->create([
        'name' => 'Scope Inventory '.str()->uuid(),
        'slug' => 'scope-inventory-'.str()->lower((string) str()->ulid()),
        'type' => 'company',
        'is_active' => true,
    ]);

    $user->scopes()->attach($scope);

    activateFilamentTenant($scope, [InventoryResource::class]);

    return $scope;
}

it('uses the barcode scanner input for the serial number field', function () {
    $serialNumberField = collect(InventoryResource::getFormDefinition())
        ->first(fn ($component) => $component->getName() === 'serial_number');

    expect($serialNumberField)->toBeInstanceOf(BarcodeScannerInput::class)
        ->toBeInstanceOf(TextInput::class);
});

it('supports the standard text input options', function () {
    $field = BarcodeScannerInput::make('serial_number')
        ->placeholder('Scan or type the serial number')
        ->maxLength(64);

    expect($field->getPlaceholder())->toBe('Scan or type the serial number')
        ->and($field->getMaxLength())->toBe(64);
});

it('creates an inventory with a scanned serial number', function () {
    $user = superAdminForInventoryResource();
    $this->actingAs($user);
    $scope = setActiveScopeForInventory($user);

    $product = Product::factory()->create();

    Livewire::test(CreateInventory::class)
        ->fillForm([
            'serial_number' => 'TEST-QR-12345',
            'product_id' => $product->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $inventory = Inventory::query()
        ->where('scope_id', $scope->id)
        ->where('serial_number', 'TEST-QR-12345')
        ->first();

    expect($inventory)->not->toBeNull()
        ->and($inventory->product_id)->toBe($product->id);
});

it('rejects a duplicate serial number', function () {
    $user = superAdminForInventoryResource();
    $this->actingAs($user);
    $scope = setActiveScopeForInventory($user);

    $product = Product::factory()->create();

    Inventory::factory()->create([
        'scope_id' => $scope->id,
        'product_id' => $product->id,
        'serial_number' => 'TEST-QR-12345',
    ]);

    Livewire::test(CreateInventory::class)
        ->fillForm([
            'serial_number' => 'TEST-QR-12345',
            'product_id' => $product->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['serial_number' => 'unique']);
});
