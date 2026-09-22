<?php

use App\Filament\Resources\ScopeResource\Pages\CreateScope;
use App\Filament\Resources\ScopeResource\Pages\EditScope;
use App\Filament\Resources\ScopeResource\Pages\ListScopes;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->ownScope = Scope::factory()->create(['is_active' => true]);
    $this->foreignScope = Scope::factory()->create(['is_active' => true]);

    $this->admin = visibilityUser('admin', $this->ownScope);
    $this->colleague = visibilityUser('user', $this->ownScope);
    $this->peerAdmin = visibilityUser('admin', $this->ownScope);
    $this->foreignUser = visibilityUser('user', $this->foreignScope);
    $this->orphan = visibilityUser('user');
});

function visibilityUser(string $role, ?Scope $scope = null): User
{
    $user = User::factory()->create();
    $user->syncRoles([$role]);

    // The factory attaches every user to the default scope: keep only the given one.
    $user->scopes()->sync($scope ? [$scope->id] : []);

    return $user;
}

function actAsVisibilityUser(object $test, User $user): void
{
    $test->actingAs($user);
    activateFilamentTenant($test->ownScope);
}

it('shows admins only the users sharing one of their scopes', function () {
    actAsVisibilityUser($this, $this->admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$this->admin, $this->colleague, $this->peerAdmin])
        ->assertCanNotSeeTableRecords([$this->foreignUser, $this->orphan]);
});

it('shows super admins every user', function () {
    $root = visibilityUser('super_admin', $this->ownScope);
    actAsVisibilityUser($this, $root);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$this->admin, $this->colleague, $this->foreignUser, $this->orphan]);
});

it('does not open users of other scopes for admins', function () {
    actAsVisibilityUser($this, $this->admin);

    $this->get(EditUser::getUrl(['record' => $this->foreignUser]))->assertNotFound();

    expect($this->admin->can('view', $this->foreignUser))->toBeFalse()
        ->and($this->admin->can('update', $this->foreignUser))->toBeFalse()
        ->and($this->admin->can('delete', $this->foreignUser))->toBeFalse();
});

it('lets admins manage only the non admin users of their scopes', function () {
    expect($this->admin->can('update', $this->colleague))->toBeTrue()
        ->and($this->admin->can('delete', $this->colleague))->toBeTrue()
        ->and($this->admin->can('view', $this->peerAdmin))->toBeTrue()
        ->and($this->admin->can('update', $this->peerAdmin))->toBeFalse()
        ->and($this->admin->can('delete', $this->peerAdmin))->toBeFalse();
});

it('lets admins edit but not delete their own account', function () {
    expect($this->admin->can('update', $this->admin))->toBeTrue()
        ->and($this->admin->can('delete', $this->admin))->toBeFalse();
});

it('lets super admins manage admins of any scope', function () {
    $root = visibilityUser('super_admin');
    $foreignAdmin = visibilityUser('admin', $this->foreignScope);

    expect($root->can('update', $foreignAdmin))->toBeTrue()
        ->and($root->can('delete', $foreignAdmin))->toBeTrue();
});

it('assigns users created by an admin to the current scope with the user role', function () {
    actAsVisibilityUser($this, $this->admin);

    Livewire::test(CreateUser::class)
        ->assertFormFieldHidden('roles')
        ->fillForm([
            'name' => 'New colleague',
            'email' => 'new-colleague@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'new-colleague@example.com')->sole();

    expect($created->hasScope($this->ownScope->id))->toBeTrue()
        ->and($created->hasScope($this->foreignScope->id))->toBeFalse()
        ->and($created->getRoleNames()->all())->toBe(['user']);
});

it('lets super admins choose the roles of new users', function () {
    $root = visibilityUser('super_admin', $this->ownScope);
    actAsVisibilityUser($this, $root);

    Livewire::test(CreateUser::class)
        ->assertFormFieldVisible('roles');
});

it('shows admins and users only their own scopes', function (string $role) {
    actAsVisibilityUser($this, $role === 'admin' ? $this->admin : $this->colleague);

    Livewire::test(ListScopes::class)
        ->assertCanSeeTableRecords([$this->ownScope])
        ->assertCanNotSeeTableRecords([$this->foreignScope]);
})->with(['admin', 'user']);

it('shows super admins every scope', function () {
    $root = visibilityUser('super_admin', $this->ownScope);
    actAsVisibilityUser($this, $root);

    Livewire::test(ListScopes::class)
        ->assertCanSeeTableRecords([$this->ownScope, $this->foreignScope]);
});

it('lets admins edit their scopes but not create, deactivate or delete scopes', function () {
    actAsVisibilityUser($this, $this->admin);

    expect($this->admin->can('update', $this->ownScope))->toBeTrue()
        ->and($this->admin->can('update', $this->foreignScope))->toBeFalse()
        ->and($this->admin->can('create', Scope::class))->toBeFalse()
        ->and($this->admin->can('changeStatus', $this->ownScope))->toBeFalse()
        ->and($this->admin->can('delete', $this->ownScope))->toBeFalse();

    $this->get(CreateScope::getUrl())->assertForbidden();

    Livewire::test(EditScope::class, ['record' => $this->ownScope->getRouteKey()])
        ->assertFormFieldDisabled('is_active')
        ->assertActionHidden('requestDeletion')
        ->fillForm(['name' => 'Renamed scope', 'is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->ownScope->fresh())
        ->name->toBe('Renamed scope')
        ->is_active->toBeTrue();

    Livewire::test(ListScopes::class)
        ->assertActionHidden(TestAction::make('requestDeletion')->table($this->ownScope));
});

it('lets super admins deactivate and request deletion of scopes', function () {
    $root = visibilityUser('super_admin', $this->ownScope);
    actAsVisibilityUser($this, $root);

    Livewire::test(EditScope::class, ['record' => $this->foreignScope->getRouteKey()])
        ->assertFormFieldEnabled('is_active')
        ->assertActionVisible('requestDeletion');
});
