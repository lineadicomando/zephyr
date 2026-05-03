<?php

declare(strict_types=1);

use Database\Seeders\ScopeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds only supported scope types including branch', function (): void {
    (new ScopeSeeder())->run();

    $types = DB::table('scopes')
        ->whereIn('slug', ['default', 'demo-school', 'demo-branch'])
        ->orderBy('slug')
        ->pluck('type', 'slug')
        ->all();

    expect($types)->toBe([
        'default' => 'company',
        'demo-branch' => 'branch',
        'demo-school' => 'school',
    ]);
});

it('throws when seeder contains an unsupported scope type', function (): void {
    $seeder = new class extends ScopeSeeder {
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
