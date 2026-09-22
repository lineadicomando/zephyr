<?php

use App\Models\User;
use Database\Seeders\BootstrapAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function setBootstrapAdminEnv(string $key, ?string $value): void
{
    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return;
    }

    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

beforeEach(function () {
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_EMAIL', 'root@example.com');
});

afterEach(function () {
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_EMAIL', null);
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', null);
});

it('creates the bootstrap admin with the configured password', function () {
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', 'first-secret');

    (new BootstrapAdminSeeder)->run();

    $admin = User::query()->where('email', 'root@example.com')->sole();

    expect(Hash::check('first-secret', $admin->password))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

it('does not reset the password of an existing admin when seeding again', function (?string $newPassword) {
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', 'first-secret');
    (new BootstrapAdminSeeder)->run();

    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', $newPassword);
    (new BootstrapAdminSeeder)->run();

    $admin = User::query()->where('email', 'root@example.com')->sole();

    expect(Hash::check('first-secret', $admin->password))->toBeTrue();
})->with([
    'different password' => 'other-secret',
    'empty password' => '',
    'missing password' => null,
]);

it('refuses to create the admin without a password', function () {
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', '');

    expect(fn () => (new BootstrapAdminSeeder)->run())->toThrow(RuntimeException::class);
    expect(User::query()->where('email', 'root@example.com')->exists())->toBeFalse();
});

it('refuses placeholder passwords in production', function (string $placeholder) {
    app()->detectEnvironment(fn (): string => 'production');
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', $placeholder);

    expect(fn () => (new BootstrapAdminSeeder)->run())->toThrow(RuntimeException::class);
})->with(BootstrapAdminSeeder::PLACEHOLDER_PASSWORDS);

it('allows the example password outside production', function () {
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', 'password');

    (new BootstrapAdminSeeder)->run();

    expect(User::query()->where('email', 'root@example.com')->exists())->toBeTrue();
});

it('restores a soft deleted bootstrap admin', function () {
    setBootstrapAdminEnv('BOOTSTRAP_ADMIN_PASSWORD', 'first-secret');
    (new BootstrapAdminSeeder)->run();
    User::query()->where('email', 'root@example.com')->sole()->delete();

    (new BootstrapAdminSeeder)->run();

    expect(User::query()->where('email', 'root@example.com')->exists())->toBeTrue();
});
