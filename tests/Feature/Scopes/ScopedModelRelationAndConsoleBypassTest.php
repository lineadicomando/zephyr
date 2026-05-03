<?php

declare(strict_types=1);

use App\Models\InventoryLocation;
use App\Models\Scope;
use App\Models\Scopes\ActiveScopeGlobalScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('resolves scope relation to the Scope model for scoped models', function (): void {
    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Scope A',
        'slug' => 'scope-a',
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $location = InventoryLocation::factory()->create([
        'scope_id' => $scopeId,
        'name' => 'Warehouse A',
    ]);

    $relation = $location->scope();

    expect($relation)->toBeInstanceOf(BelongsTo::class)
        ->and(get_class($relation->getRelated()))->toBe(Scope::class);
});

it('allows console bypass only for explicit maintenance commands', function (): void {
    expect(ActiveScopeGlobalScope::shouldBypassScopeFilterForConsoleCommand('migrate'))->toBeTrue()
        ->and(ActiveScopeGlobalScope::shouldBypassScopeFilterForConsoleCommand('db:seed'))->toBeTrue()
        ->and(ActiveScopeGlobalScope::shouldBypassScopeFilterForConsoleCommand('queue:work'))->toBeFalse()
        ->and(ActiveScopeGlobalScope::shouldBypassScopeFilterForConsoleCommand(null))->toBeFalse();
});
