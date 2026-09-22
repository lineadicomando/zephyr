<?php

use App\Models\User;
use Database\Seeders\BootstrapAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function setBootstrapAdminConfig(string $key, ?string $value): void
{
    config()->set("app.bootstrap_admin.{$key}", $value);
}

beforeEach(function () {
    setBootstrapAdminConfig('email', 'root@example.com');
});

it('creates the bootstrap admin with the configured password', function () {
    setBootstrapAdminConfig('password', 'first-secret');

    (new BootstrapAdminSeeder)->run();

    $admin = User::query()->where('email', 'root@example.com')->sole();

    expect(Hash::check('first-secret', $admin->password))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

it('does not reset the password of an existing admin when seeding again', function (?string $newPassword) {
    setBootstrapAdminConfig('password', 'first-secret');
    (new BootstrapAdminSeeder)->run();

    setBootstrapAdminConfig('password', $newPassword);
    (new BootstrapAdminSeeder)->run();

    $admin = User::query()->where('email', 'root@example.com')->sole();

    expect(Hash::check('first-secret', $admin->password))->toBeTrue();
})->with([
    'different password' => 'other-secret',
    'empty password' => '',
    'missing password' => null,
]);

it('refuses to create the admin without a password', function () {
    setBootstrapAdminConfig('password', '');

    expect(fn () => (new BootstrapAdminSeeder)->run())->toThrow(RuntimeException::class);
    expect(User::query()->where('email', 'root@example.com')->exists())->toBeFalse();
});

it('refuses placeholder passwords in production', function (string $placeholder) {
    app()->detectEnvironment(fn (): string => 'production');
    setBootstrapAdminConfig('password', $placeholder);

    expect(fn () => (new BootstrapAdminSeeder)->run())->toThrow(RuntimeException::class);
})->with(BootstrapAdminSeeder::PLACEHOLDER_PASSWORDS);

it('allows the example password outside production', function () {
    setBootstrapAdminConfig('password', 'password');

    (new BootstrapAdminSeeder)->run();

    expect(User::query()->where('email', 'root@example.com')->exists())->toBeTrue();
});

it('restores a soft deleted bootstrap admin', function () {
    setBootstrapAdminConfig('password', 'first-secret');
    (new BootstrapAdminSeeder)->run();
    User::query()->where('email', 'root@example.com')->sole()->delete();

    (new BootstrapAdminSeeder)->run();

    expect(User::query()->where('email', 'root@example.com')->exists())->toBeTrue();
});
