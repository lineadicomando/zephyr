<?php

use App\Models\User;
use App\Filament\Widgets\CurrentScopeWidget;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
});

it('shows current scope and type in dashboard widget', function () {
    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Scope Widget',
        'slug' => 'scope-widget',
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

    $this->actingAs($user)
        ->withSession(['active_scope_id' => $scopeId]);

    Livewire::test(CurrentScopeWidget::class)
        ->assertSee('Scope Widget')
        ->assertSee('Type: company')
        ->assertSee('scope-widget');
});
