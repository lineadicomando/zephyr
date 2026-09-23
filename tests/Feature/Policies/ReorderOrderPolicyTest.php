<?php

use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\User;
use App\Policies\ReorderOrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('allows delete only for draft status when user has delete permission', function () {
    Permission::query()->firstOrCreate(['name' => 'Delete:ReorderOrder', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('Delete:ReorderOrder');

    $policy = new ReorderOrderPolicy;

    $scope = Scope::factory()->create();
    $user->scopes()->attach($scope->id);

    $draft = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);
    $requested = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_REQUESTED]);

    expect($policy->delete($user, $draft))->toBeTrue()
        ->and($policy->delete($user, $requested))->toBeFalse();
});

it('requires transition permission for reorder order transitions', function () {
    Permission::query()->firstOrCreate(['name' => 'Transition:ReorderOrder', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $policy = new ReorderOrderPolicy;

    expect($policy->transition($user))->toBeFalse();

    $user->givePermissionTo('Transition:ReorderOrder');
    expect($policy->transition($user))->toBeTrue();
});
