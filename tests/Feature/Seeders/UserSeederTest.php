<?php

use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

it('attaches the active scopes only to the demo users', function () {
    $scope = Scope::factory()->create(['is_active' => true]);
    $realUser = User::factory()->create();
    $realUser->scopes()->sync([]);

    (new UserSeeder)->run();

    $demoAdmin = User::query()->where('email', 'marco.bianchi@example.local')->sole();

    expect($demoAdmin->hasScope($scope->id))->toBeTrue()
        ->and($realUser->fresh()->scopes()->exists())->toBeFalse();
});

it('gives the demo users the well known password only outside production', function (string $environment, bool $knownPassword) {
    app()->detectEnvironment(fn (): string => $environment);

    (new UserSeeder)->run();

    $demoAdmin = User::query()->where('email', 'marco.bianchi@example.local')->sole();

    expect(Hash::check('password', $demoAdmin->password))->toBe($knownPassword);
})->with([
    'local' => ['local', true],
    'production' => ['production', false],
]);
