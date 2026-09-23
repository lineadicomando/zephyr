<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\EditInventory;
use App\Filament\Resources\InventoryResource\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\InventoryResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\MovementItemResource;
use App\Filament\Resources\MovementResource;
use App\Filament\Resources\ReorderOrderResource;
use App\Filament\Resources\ReorderOrderResource\Pages\ListReorderOrders;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Resources\TaskResource\Widgets\TaskCalendarWidget;
use App\Models\Inventory;
use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Services\Reorders\ReorderProposalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);
});

function accessControlUser(object $test, string ...$permissions): User
{
    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo($permissions);
    $user->scopes()->attach($test->scope);

    return $user;
}

function accessControlTask(object $test, User $owner): Task
{
    return Task::factory()->create([
        'scope_id' => $test->scope->id,
        'starts_at' => '2026-05-01 09:00:00',
        'ends_at' => '2026-05-01 10:00:00',
        'task_type_id' => TaskType::factory(),
        'task_status_id' => TaskStatus::factory(),
        'user_id' => $owner->id,
    ]);
}

it('hides reorder order transitions and proposals from users without permission', function () {
    $user = accessControlUser($this, 'ViewAny:ReorderOrder', 'View:ReorderOrder');
    $this->actingAs($user);
    activateFilamentTenant($this->scope, [ReorderOrderResource::class]);

    $draft = ReorderOrder::query()->create(['scope_id' => $this->scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);

    Livewire::test(ListReorderOrders::class)
        ->assertActionHidden(TestAction::make('request')->table($draft))
        ->assertActionHidden(TestAction::make('cancel')->table($draft))
        ->assertActionHidden(TestAction::make('generateCriticalProposal')->table());
});

it('shows reorder order transitions and proposals to authorized users', function () {
    $user = accessControlUser($this, 'ViewAny:ReorderOrder', 'View:ReorderOrder', 'Transition:ReorderOrder', 'Create:ReorderOrder');
    $this->actingAs($user);
    activateFilamentTenant($this->scope, [ReorderOrderResource::class]);

    $draft = ReorderOrder::query()->create(['scope_id' => $this->scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);

    Livewire::test(ListReorderOrders::class)
        ->assertActionVisible(TestAction::make('request')->table($draft))
        ->assertActionVisible(TestAction::make('generateCriticalProposal')->table());
});

it('does not create an empty proposal when no reorder rule is critical', function () {
    $this->actingAs(accessControlUser($this, 'ViewAny:ReorderOrder', 'Create:ReorderOrder'));
    activateFilamentTenant($this->scope, [ReorderOrderResource::class]);

    expect(app(ReorderProposalService::class)->createDraftFromCritical())->toBeNull();

    Livewire::test(ListReorderOrders::class)
        ->callAction(TestAction::make('generateCriticalProposal')->table())
        ->assertNotified(__('No critical items to reorder'));

    expect(ReorderOrder::query()->count())->toBe(0);
});

it('lets non admin users access only their own tasks', function () {
    $user = accessControlUser($this, 'View:Task', 'Update:Task', 'Delete:Task');
    $colleague = accessControlUser($this);
    $admin = accessControlUser($this);
    $admin->assignRole('admin');

    $ownTask = accessControlTask($this, $user);
    $colleagueTask = accessControlTask($this, $colleague);

    expect($user->can('view', $ownTask))->toBeTrue()
        ->and($user->can('update', $ownTask))->toBeTrue()
        ->and($user->can('view', $colleagueTask))->toBeFalse()
        ->and($user->can('update', $colleagueTask))->toBeFalse()
        ->and($user->can('delete', $colleagueTask))->toBeFalse()
        ->and($admin->can('update', $colleagueTask))->toBeTrue();
});

it('does not move calendar tasks for users who cannot update them', function (string $method) {
    $owner = accessControlUser($this);
    $user = accessControlUser($this, 'ViewAny:Task', 'View:Task');
    $task = accessControlTask($this, $owner);

    $this->actingAs($user);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    $event = ['id' => $task->id, 'start' => '2026-06-01 09:00:00', 'end' => '2026-06-01 10:00:00'];
    $widget = new TaskCalendarWidget;

    $shouldRevert = $method === 'drop'
        ? $widget->onEventDrop($event, [], [], [], null, null)
        : $widget->onEventResize($event, [], [], [], []);

    expect($shouldRevert)->toBeTrue()
        ->and($task->fresh()->starts_at)->toBe('2026-05-01 09:00:00');
})->with(['drop', 'resize']);

it('only allows assigning tasks to users of the current tenant', function () {
    $foreignUser = User::factory()->create();
    $foreignUser->scopes()->attach(Scope::factory()->create());

    $admin = accessControlUser($this);
    $admin->assignRole('admin');
    $this->actingAs($admin);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(CreateTask::class)
        ->fillForm([
            'task_type_id' => TaskType::factory()->create()->id,
            'task_status_id' => TaskStatus::factory()->create()->id,
            'description' => 'Assigned task',
            'user_id' => $foreignUser->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['user_id']);
});

it('denies access to inactive tenants', function () {
    $user = accessControlUser($this);
    $inactive = Scope::factory()->create(['is_active' => false]);
    $user->scopes()->attach($inactive);

    expect($user->canAccessTenant($this->scope))->toBeTrue()
        ->and($user->canAccessTenant($inactive))->toBeFalse();
});

it('grants the reorder order transition permission to the preset roles', function (string $role) {
    $user = User::factory()->create();
    $user->syncRoles([$role]);

    $draft = ReorderOrder::query()->create(['scope_id' => $this->scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);
    $user->scopes()->attach($this->scope);

    expect($user->can('transition', $draft))->toBeTrue();
})->with(['admin', 'user']);

it('creates the transition permission for existing installations', function () {
    Permission::query()->where('name', 'Transition:ReorderOrder')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    (require database_path('migrations/2026_09_22_075520_add_transition_reorder_order_permission.php'))->up();
    (require database_path('migrations/2026_09_23_082302_rename_permissions_to_the_shield_format.php'))->up();

    expect(Role::findByName('user')->hasPermissionTo('Transition:ReorderOrder'))->toBeTrue()
        ->and(Role::findByName('admin')->hasPermissionTo('Transition:ReorderOrder'))->toBeTrue();
});

it('shows non admin users only their own tasks on the inventory tasks tab', function () {
    $user = accessControlUser($this, 'ViewAny:Task', 'View:Task', 'Update:Task');
    $colleague = accessControlUser($this);

    $ownTask = accessControlTask($this, $user);
    $colleagueTask = accessControlTask($this, $colleague);

    $inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $inventory->tasks()->attach([$ownTask->id, $colleagueTask->id]);

    $this->actingAs($user);
    activateFilamentTenant($this->scope, [InventoryResource::class, TaskResource::class]);

    Livewire::test(TasksRelationManager::class, ['ownerRecord' => $inventory, 'pageClass' => EditInventory::class])
        ->assertCanSeeTableRecords([$ownTask])
        ->assertCanNotSeeTableRecords([$colleagueTask])
        ->assertActionVisible(TestAction::make('detach')->table($ownTask));

    expect($user->can('detach', $colleagueTask))->toBeFalse()
        ->and($user->can('detach', $ownTask))->toBeTrue();
});

it('checks the role permissions generated by shield', function () {
    $roleManager = accessControlUser($this, 'ViewAny:Role', 'Update:Role');
    $user = accessControlUser($this);
    $role = Role::findByName('user');

    expect($roleManager->can('viewAny', Role::class))->toBeTrue()
        ->and($roleManager->can('update', $role))->toBeTrue()
        ->and($roleManager->can('delete', $role))->toBeFalse()
        ->and($user->can('viewAny', Role::class))->toBeFalse();
});

it('needs the movement permissions to create movements from the inventory movements tab', function (array $permissions, bool $canCreate) {
    $this->actingAs(accessControlUser($this, 'ViewAny:MovementItem', 'View:MovementItem', 'Create:MovementItem', ...$permissions));
    activateFilamentTenant($this->scope, [InventoryResource::class, MovementResource::class, MovementItemResource::class]);

    $inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);

    $component = Livewire::test(MovementsRelationManager::class, ['ownerRecord' => $inventory, 'pageClass' => EditInventory::class]);

    $canCreate
        ? $component->assertActionVisible(TestAction::make('create')->table())
        : $component->assertActionHidden(TestAction::make('create')->table());
})->with([
    'movement item permissions only' => [[], false],
    'movement permissions too' => [['Create:Movement'], true],
]);
