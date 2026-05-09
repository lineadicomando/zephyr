<?php

use App\Filament\Resources\ReorderOrderResource;
use App\Filament\Resources\ReorderOrderResource\Pages\ListReorderOrders;
use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

function superAdminForReorderResource(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

function setActiveScopeFor(User $user): Scope
{
    $scope = Scope::factory()->create([
        'name' => 'Scope Reorder '.str()->uuid(),
        'slug' => 'scope-reorder-'.str()->lower((string) str()->ulid()),
        'type' => 'company',
        'is_active' => true,
    ]);

    $user->scopes()->attach($scope);

    activateFilamentTenant($scope, [ReorderOrderResource::class]);

    return $scope;
}

it('shows reorder table actions according to order status', function () {
    $user = superAdminForReorderResource();
    $this->actingAs($user);
    $scope = setActiveScopeFor($user);

    $draft = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);
    $requested = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_REQUESTED]);
    $ordered = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_ORDERED]);
    $received = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_RECEIVED]);
    $cancelled = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_CANCELLED]);

    Livewire::test(ListReorderOrders::class)
        ->assertCanSeeTableRecords([$draft, $requested, $ordered, $received, $cancelled])
        ->assertTableActionVisible('request', $draft)
        ->assertTableActionHidden('request', $requested)
        ->assertTableActionVisible('markOrdered', $requested)
        ->assertTableActionHidden('markOrdered', $draft)
        ->assertTableActionVisible('markReceived', $ordered)
        ->assertTableActionHidden('markReceived', $requested)
        ->assertTableActionVisible('cancel', $draft)
        ->assertTableActionVisible('cancel', $requested)
        ->assertTableActionVisible('cancel', $ordered)
        ->assertTableActionHidden('cancel', $received)
        ->assertTableActionHidden('cancel', $cancelled);
});

it('shows view action to a read-only user and edit action to an update-capable user', function () {
    Permission::query()->firstOrCreate(['name' => 'view_any_reorder_order', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'view_reorder_order', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'update_reorder_order', 'guard_name' => 'web']);

    $readOnly = User::factory()->create();
    $readOnly->syncRoles([]);
    $this->actingAs($readOnly);
    $scope = setActiveScopeFor($readOnly);

    $order = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);

    $readOnly->givePermissionTo('view_any_reorder_order');
    $readOnly->givePermissionTo('view_reorder_order');
    expect($readOnly->can('update', $order))->toBeFalse();

    Livewire::test(ListReorderOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->assertTableActionVisible('view', $order)
        ->assertTableActionHidden('edit', $order);

    $slug = $scope->slug;
    $this->get("/{$slug}/reorder-orders/{$order->id}/view")->assertOk();
    $this->get("/{$slug}/reorder-orders/{$order->id}/edit")->assertForbidden();

    $updater = User::factory()->create();
    $updater->syncRoles([]);
    $updater->scopes()->attach($scope->id);
    $updater->givePermissionTo('view_any_reorder_order');
    $updater->givePermissionTo('view_reorder_order');
    $updater->givePermissionTo('update_reorder_order');

    $this->actingAs($updater);
    activateFilamentTenant($scope, [ReorderOrderResource::class]);
    Livewire::test(ListReorderOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->assertTableActionVisible('edit', $order);

    $this->get("/{$slug}/reorder-orders/{$order->id}/edit")->assertOk();
});
