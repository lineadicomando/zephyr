<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
});

it('allows super admin to manage scopes pages', function () {
    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Scope UX',
        'slug' => 'scope-ux',
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    DB::table('scope_user')->insert([
        'scope_id' => $scopeId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get('/scopes')->assertOk();
    $this->get('/scopes/create')->assertOk();
    $this->get("/scopes/{$scopeId}/view")->assertOk();
    $this->get("/scopes/{$scopeId}/edit")->assertOk();
});

it('prevents edit for read-only users while allowing scope view', function () {
    Permission::query()->firstOrCreate(['name' => 'view_any_scope', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'view_scope', 'guard_name' => 'web']);

    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Scope ReadOnly',
        'slug' => 'scope-read-only',
        'type' => 'school',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo('view_any_scope');
    $user->givePermissionTo('view_scope');
    DB::table('scope_user')->insert([
        'scope_id' => $scopeId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get('/scopes')->assertOk();
    $this->get("/scopes/{$scopeId}/view")->assertOk();
    $this->get("/scopes/{$scopeId}/edit")->assertForbidden();
});

it('shows topbar scope switcher with assigned active scopes', function () {
    $scopeAId = DB::table('scopes')->insertGetId([
        'name' => 'Scope Topbar A',
        'slug' => 'scope-topbar-a',
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $scopeBId = DB::table('scopes')->insertGetId([
        'name' => 'Scope Topbar B',
        'slug' => 'scope-topbar-b',
        'type' => 'school',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    DB::table('scope_user')->insert([
        ['scope_id' => $scopeAId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ['scope_id' => $scopeBId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->actingAs($user)
        ->withSession(['active_scope_id' => $scopeAId])
        ->get('/')
        ->assertOk()
        ->assertSee('Scope Topbar A (company)')
        ->assertSee('Scope Topbar B (school)')
        ->assertSee("/scopes/{$scopeAId}/switch");
});
