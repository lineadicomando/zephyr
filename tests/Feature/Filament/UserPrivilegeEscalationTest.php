<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers\ScopesRelationManager;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->ownScope = Scope::factory()->create(['is_active' => true]);
    $this->foreignScope = Scope::factory()->create(['is_active' => true]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->scopes()->attach($this->ownScope);

    $this->actingAs($this->admin);

    activateFilamentTenant($this->ownScope);
});

it('does not let an admin grant the super_admin role', function () {
    $target = User::factory()->create();
    $target->assignRole('user');
    $target->scopes()->attach($this->ownScope);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm([
            'roles' => [Role::findByName('super_admin')->id],
        ])
        ->call('save');

    expect($target->fresh()->hasRole('super_admin'))->toBeFalse();
});

it('does not let an admin promote a user to admin', function () {
    $target = User::factory()->create();
    $target->syncRoles(['user']);
    $target->scopes()->attach($this->ownScope);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->assertFormFieldHidden('roles')
        ->fillForm(['roles' => [Role::findByName('admin')->id]])
        ->call('save');

    expect($target->fresh()->hasRole('admin'))->toBeFalse();
});

it('does not let an admin manage a super_admin account', function () {
    $root = User::factory()->create();
    $root->assignRole('super_admin');
    $root->scopes()->attach($this->ownScope);

    $colleague = User::factory()->create();
    $colleague->scopes()->attach($this->ownScope);

    expect($this->admin->can('update', $root))->toBeFalse()
        ->and($this->admin->can('delete', $root))->toBeFalse()
        ->and($this->admin->can('forceDelete', $root))->toBeFalse()
        ->and($this->admin->can('update', $colleague))->toBeTrue();
});

it('lets a super_admin manage another super_admin account', function () {
    $root = User::factory()->create();
    $root->assignRole('super_admin');

    $otherRoot = User::factory()->create();
    $otherRoot->assignRole('super_admin');

    expect($root->can('update', $otherRoot))->toBeTrue();
});

it('does not let an admin attach a user to a scope they do not belong to', function () {
    $target = User::factory()->create();
    $target->scopes()->attach($this->ownScope);

    Livewire::test(ScopesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('attach')->table(), [
            'recordId' => $this->foreignScope->id,
        ])
        ->assertHasFormErrors(['recordId']);

    expect($target->fresh()->hasScope($this->foreignScope->id))->toBeFalse();
});

it('lets an admin attach a user to a scope they belong to', function () {
    $target = User::factory()->create();

    Livewire::test(ScopesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('attach')->table(), [
            'recordId' => $this->ownScope->id,
        ])
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasScope($this->ownScope->id))->toBeTrue();
});

it('does not let an admin see or detach a scope they do not belong to', function () {
    $target = User::factory()->create();
    $target->scopes()->attach([$this->ownScope->id, $this->foreignScope->id]);

    Livewire::test(ScopesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->assertCanSeeTableRecords([$this->ownScope])
        ->assertCanNotSeeTableRecords([$this->foreignScope])
        ->assertActionVisible(TestAction::make('detach')->table($this->ownScope));

    expect($this->admin->can('manageMembership', $this->foreignScope))->toBeFalse();
});
