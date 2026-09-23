<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('purges expired pending scope and scope_user rows', function (): void {
    Carbon::setTestNow('2026-05-03 10:00:00');

    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Purge Me',
        'slug' => 'purge-me',
        'type' => 'company',
        'is_active' => false,
        'protected' => false,
        'pending_delete' => now()->subHour(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userId = DB::table('users')->insertGetId([
        'name' => 'Test User',
        'email' => 'scope-purge@example.test',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('scope_user')->insert([
        'scope_id' => $scopeId,
        'user_id' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('scopes:purge-pending')->assertExitCode(0);

    expect(DB::table('scopes')->where('id', $scopeId)->exists())->toBeFalse()
        ->and(DB::table('scope_user')->where('scope_id', $scopeId)->exists())->toBeFalse();
});

it('does not purge future pending scopes', function (): void {
    Carbon::setTestNow('2026-05-03 10:00:00');

    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Future Scope',
        'slug' => 'future-scope',
        'type' => 'company',
        'is_active' => false,
        'protected' => false,
        'pending_delete' => now()->addHour(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('scopes:purge-pending')->assertExitCode(0);

    expect(DB::table('scopes')->where('id', $scopeId)->exists())->toBeTrue();
});

it('does not purge protected scopes even if pending_delete is expired', function (): void {
    Carbon::setTestNow('2026-05-03 10:00:00');

    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Protected Scope',
        'slug' => 'protected-purge-scope',
        'type' => 'company',
        'is_active' => false,
        'protected' => true,
        'pending_delete' => now()->subHour(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('scopes:purge-pending')->assertExitCode(0);

    expect(DB::table('scopes')->where('id', $scopeId)->exists())->toBeTrue();
});

it('fails when a pending scope cannot be purged', function (): void {
    Carbon::setTestNow('2026-05-03 10:00:00');

    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Broken Purge',
        'slug' => 'broken-purge',
        'type' => 'company',
        'is_active' => false,
        'protected' => false,
        'pending_delete' => now()->subHour(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::listen(function ($query): void {
        if (preg_match('/^delete from [`"]scopes[`"]/', $query->sql) === 1) {
            throw new RuntimeException('Simulated purge failure');
        }
    });

    $this->artisan('scopes:purge-pending')->assertFailed();

    expect(DB::table('scopes')->where('id', $scopeId)->exists())->toBeTrue();
});

it('purges a scope with its whole domain data and leaves the other scopes alone', function (): void {
    (new Database\Seeders\RolesAndPermissionsSeeder)->run();

    $makeDomain = function (App\Models\Scope $scope): App\Models\User {
        $user = App\Models\User::factory()->inScope($scope)->create();
        $user->syncRolesInScope($scope->id, [Spatie\Permission\Models\Role::findByName('admin', 'web')->id]);

        $load = App\Models\Movement::factory()->create(['scope_id' => $scope->id]);
        $inventory = App\Models\Inventory::factory()->create(['scope_id' => $scope->id]);
        $item = App\Models\MovementItem::query()->create(['scope_id' => $scope->id, 'movement_id' => $load->id, 'inventory_id' => $inventory->id, 'stock' => 2]);
        $reorder = App\Models\Reorder::factory()->create(['scope_id' => $scope->id, 'stock_id' => $item->incoming_stock_id, 'reorder_point' => 5]);
        $order = App\Models\ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => App\Models\ReorderOrder::STATUS_DRAFT]);
        $order->items()->create(['scope_id' => $scope->id, 'stock_id' => $reorder->stock_id, 'reorder_id' => $reorder->id, 'current_stock' => 2, 'reorder_point' => 5, 'suggested_qty' => 3]);
        App\Models\Task::factory()->create(['scope_id' => $scope->id, 'user_id' => $user->id])->inventories()->attach($inventory);

        return $user;
    };

    $purged = App\Models\Scope::factory()->create(['is_active' => true]);
    $kept = App\Models\Scope::factory()->create(['is_active' => true]);
    $purgedUser = $makeDomain($purged);
    $makeDomain($kept);
    $purged->forceFill(['is_active' => false, 'pending_delete' => now()->subHour()])->saveQuietly();

    $counts = fn (int $scopeId): array => collect([...App\Support\Scope\ScopePurgeRegistry::tables(), 'scope_user'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->where('scope_id', $scopeId)->count()])
        ->all();
    $keptBefore = $counts($kept->id);

    $this->artisan('scopes:purge-pending')->assertSuccessful();

    expect(array_filter($counts($purged->id)))->toBe([])
        ->and($counts($kept->id))->toBe($keptBefore)
        ->and(DB::table('scopes')->where('id', $purged->id)->exists())->toBeFalse()
        ->and(DB::table('task_inventory')->count())->toBe(1)
        ->and(App\Models\User::query()->find($purgedUser->id))->not->toBeNull();
});
