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

