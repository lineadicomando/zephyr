<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\EditInventory;
use App\Filament\Resources\ReorderOrderResource;
use App\Filament\Resources\ReorderOrderResource\Pages\EditReorderOrder;
use App\Models\Inventory;
use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->user = User::factory()->create();
    $this->user->assignRole('admin');

    $this->scope = Scope::factory()->create(['is_active' => true]);
    $this->otherScope = Scope::factory()->create(['is_active' => true]);

    $this->user->scopes()->attach($this->scope);
    $this->actingAs($this->user);

    activateFilamentTenant($this->scope, [InventoryResource::class, ReorderOrderResource::class]);
});

it('does not move an inventory to another scope when scope_id is tampered on edit', function () {
    $inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);

    Livewire::test(EditInventory::class, ['record' => $inventory->getRouteKey()])
        ->set('data.scope_id', $this->otherScope->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($inventory->fresh()->scope_id)->toBe($this->scope->id);
});

it('does not move a reorder order to another scope when scope_id is tampered on edit', function () {
    $order = ReorderOrder::query()->create([
        'scope_id' => $this->scope->id,
        'status' => ReorderOrder::STATUS_DRAFT,
    ]);

    Livewire::test(EditReorderOrder::class, ['record' => $order->getRouteKey()])
        ->set('data.scope_id', $this->otherScope->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($order->fresh()->scope_id)->toBe($this->scope->id);
});
