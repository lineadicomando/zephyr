<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

it('creates a token with the given abilities', function () {
    $user = User::factory()->create(['email' => 'ansible@example.com']);

    $this->artisan('api-tokens:create', ['email' => 'ansible@example.com', '--name' => 'ansible', '--ability' => ['user:read']])
        ->expectsOutputToContain('Copy it now')
        ->assertSuccessful();

    $token = PersonalAccessToken::query()->sole();

    expect($token->name)->toBe('ansible')
        ->and($token->abilities)->toBe(['user:read'])
        ->and($token->tokenable->is($user))->toBeTrue();
});

it('refuses unknown abilities and unknown users', function () {
    User::factory()->create(['email' => 'ansible@example.com']);

    $this->artisan('api-tokens:create', ['email' => 'ansible@example.com', '--ability' => ['everything']])
        ->expectsOutputToContain('Unknown abilities: everything')
        ->assertFailed();

    $this->artisan('api-tokens:create', ['email' => 'nobody@example.com', '--ability' => ['user:read']])
        ->assertFailed();

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('lists and revokes tokens', function () {
    $user = User::factory()->create(['email' => 'ansible@example.com']);
    $token = $user->createToken('ansible', ['products:read'])->accessToken;

    $this->artisan('api-tokens:list', ['email' => 'ansible@example.com'])
        ->expectsTable(['#', 'User', 'Name', 'Abilities', 'Last used', 'Expires'], [[
            $token->id, 'ansible@example.com', 'ansible', 'products:read', '-', $token->created_at->addMinutes(config('sanctum.expiration'))->toDateTimeString(),
        ]])
        ->assertSuccessful();

    $this->artisan('api-tokens:revoke', ['id' => $token->id])->assertSuccessful();
    $this->artisan('api-tokens:revoke', ['id' => $token->id])->assertFailed();

    expect(PersonalAccessToken::query()->count())->toBe(0);
});
