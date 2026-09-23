<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers\ScopesRelationManager;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductType;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scopeA = Scope::factory()->create(['is_active' => true]);
    $this->scopeB = Scope::factory()->create(['is_active' => true]);
});

function roleId(string $name): int
{
    return Role::findByName($name, 'web')->getKey();
}

function scopeMember(object $test): User
{
    $user = User::factory()->create();
    // The factory gives the global user role: only the per scope roles matter here.
    $user->syncRoles([]);
    $user->scopes()->sync([$test->scopeA->id, $test->scopeB->id]);

    return $user;
}

it('applies a role only in the scope it was assigned in', function () {
    $user = scopeMember($this);
    $user->syncRolesInScope($this->scopeA->id, [roleId('admin')]);
    $user->syncRolesInScope($this->scopeB->id, [roleId('user')]);
    $this->actingAs($user);

    activateFilamentTenant($this->scopeA);
    expect($user->isAdmin())->toBeTrue()
        ->and($user->can('DeleteAny:Inventory'))->toBeTrue();

    activateFilamentTenant($this->scopeB);
    expect($user->isAdmin())->toBeFalse()
        ->and($user->can('DeleteAny:Inventory'))->toBeFalse()
        ->and($user->can('ViewAny:Inventory'))->toBeTrue();
});

it('keeps super admins super admin in every scope', function () {
    $root = scopeMember($this);
    $root->setRoot(true);
    $this->actingAs($root);

    activateFilamentTenant($this->scopeB);

    expect($root->isRoot())->toBeTrue()
        ->and($root->can('Create:Scope'))->toBeTrue()
        ->and($root->isAdminInAnyScope())->toBeTrue();
});

it('lets super admins set the roles of the current scope and the super admin flag', function () {
    $root = scopeMember($this);
    $root->setRoot(true);
    $target = scopeMember($this);
    $target->syncRolesInScope($this->scopeB->id, [roleId('user')]);

    $this->actingAs($root);
    activateFilamentTenant($this->scopeA);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['scope_roles' => [roleId('admin')], 'is_super_admin' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->rolesInScope($this->scopeA->id)->pluck('name')->all())->toBe(['admin'])
        ->and($target->rolesInScope($this->scopeB->id)->pluck('name')->all())->toBe(['user'])
        ->and($target->fresh()->isRoot())->toBeFalse();

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['is_super_admin' => true])
        ->call('save');

    expect($target->fresh()->isRoot())->toBeTrue();
});

it('gives the user role when attaching a scope and removes the roles when detaching it', function () {
    $root = scopeMember($this);
    $root->setRoot(true);
    $target = User::factory()->create();
    $target->scopes()->sync([$this->scopeA->id]);

    $this->actingAs($root);
    activateFilamentTenant($this->scopeA);

    $component = Livewire::test(ScopesRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
        ->callAction(TestAction::make('attach')->table(), ['recordId' => $this->scopeB->id]);

    expect($target->rolesInScope($this->scopeB->id)->pluck('name')->all())->toBe(['user']);

    $component->callAction(TestAction::make('detach')->table($this->scopeB));

    expect($target->rolesInScope($this->scopeB->id))->toBeEmpty();
});

it('lets api users read products with a permission granted in one of their scopes', function () {
    $group = ProductGroup::query()->create(['name' => 'Group']);
    $type = ProductType::query()->create(['name' => 'Type']);
    Product::query()->create(['product_group_id' => $group->id, 'product_type_id' => $type->id, 'name' => 'Product']);

    $user = scopeMember($this);
    $user->syncRolesInScope($this->scopeB->id, [roleId('user')]);
    Sanctum::actingAs($user, ['products:read']);

    $this->getJson('/api/products')->assertOk();

    $user->syncRolesInScope($this->scopeB->id, []);
    Sanctum::actingAs($user->fresh(), ['products:read']);

    $this->getJson('/api/products')->assertForbidden();
});

it('purges the role assignments of a deleted scope', function () {
    $user = scopeMember($this);
    $user->syncRolesInScope($this->scopeB->id, [roleId('admin')]);
    $this->scopeB->forceFill(['is_active' => false, 'pending_delete' => now()->subDay()])->saveQuietly();

    Artisan::call('scopes:purge-pending');

    expect(DB::table('model_has_roles')->where('scope_id', $this->scopeB->id)->exists())->toBeFalse();
});

it('moves existing role assignments into the scopes of their users', function () {
    $root = scopeMember($this);
    $admin = scopeMember($this);
    $withoutScopes = scopeMember($this);
    $withoutScopes->scopes()->sync([]);
    $roleIds = ['super_admin' => roleId('super_admin'), 'admin' => roleId('admin'), 'user' => roleId('user')];

    $migration = require database_path('migrations/2026_09_23_062607_add_scope_teams_to_permission_tables.php');
    $migration->down();

    foreach ([[$root, 'super_admin'], [$admin, 'admin'], [$withoutScopes, 'user']] as [$user, $role]) {
        DB::table('model_has_roles')->insert(['role_id' => $roleIds[$role], 'model_type' => $user->getMorphClass(), 'model_id' => $user->id]);
    }

    $migration->up();

    $teams = fn (User $user): array => DB::table('model_has_roles')->where('model_id', $user->id)->orderBy('scope_id')->pluck('scope_id')->all();

    expect($teams($root))->toBe([Scope::GLOBAL_PERMISSIONS_TEAM])
        ->and($teams($admin))->toBe([$this->scopeA->id, $this->scopeB->id])
        ->and($teams($withoutScopes))->toBe([Scope::GLOBAL_PERMISSIONS_TEAM]);
});
