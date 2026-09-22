<?php

declare(strict_types=1);

use Database\Seeders\DemoScopeSeeder;
use Database\Seeders\ScopeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds only the default scope as baseline', function (): void {
    (new ScopeSeeder)->run();

    expect(DB::table('scopes')->where('slug', 'default')->value('type'))->toBe('company')
        ->and(DB::table('scopes')->whereIn('slug', ['demo-school', 'demo-branch'])->exists())->toBeFalse();
});

it('seeds the demo scopes with supported types', function (): void {
    (new DemoScopeSeeder)->run();

    expect(DB::table('scopes')->whereIn('slug', ['demo-school', 'demo-branch'])->orderBy('slug')->pluck('type', 'slug')->all())->toBe([
        'demo-branch' => 'branch',
        'demo-school' => 'school',
    ]);
});

it('leaves existing scopes untouched when seeding again', function (): void {
    $createdAt = now()->subYear()->startOfSecond();
    $pendingDelete = now()->addDay()->startOfSecond();

    DB::table('scopes')->where('slug', 'default')->delete();
    DB::table('scopes')->insert([
        'slug' => 'default',
        'name' => 'Renamed',
        'type' => 'school',
        'is_active' => false,
        'pending_delete' => $pendingDelete,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    (new ScopeSeeder)->run();

    $scope = DB::table('scopes')->where('slug', 'default')->first();

    expect(DB::table('scopes')->count())->toBe(1)
        ->and($scope->name)->toBe('Renamed')
        ->and((bool) $scope->is_active)->toBeFalse()
        ->and(Carbon::parse($scope->pending_delete)->equalTo($pendingDelete))->toBeTrue()
        ->and(Carbon::parse($scope->created_at)->equalTo($createdAt))->toBeTrue();
});

it('throws when seeder contains an unsupported scope type', function (): void {
    $seeder = new class extends ScopeSeeder
    {
        protected function scopes(): array
        {
            return [
                ['slug' => 'invalid', 'name' => 'Invalid', 'type' => 'invalid-type'],
            ];
        }
    };

    expect(fn () => $seeder->run())
        ->toThrow(InvalidArgumentException::class, 'Unsupported scope type [invalid-type] for slug [invalid].');
});
