<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('throttles api requests', function () {
    config()->set('sanctum.rate_limit_per_minute', 2);
    RateLimiter::clear('api');

    Sanctum::actingAs(User::factory()->create(), ['user:read']);

    $this->getJson('/api/user')->assertOk();
    $this->getJson('/api/user')->assertOk();
    $this->getJson('/api/user')->assertTooManyRequests();
});

it('rejects expired api tokens when an expiration is configured', function () {
    config()->set('sanctum.expiration', 60);

    $user = User::factory()->create();
    $token = $user->createToken('integration');

    PersonalAccessToken::query()->whereKey($token->accessToken->getKey())->update(['created_at' => now()->subHours(2)]);

    $this->withToken($token->plainTextToken)
        ->getJson('/api/user')
        ->assertUnauthorized();
});

it('accepts api tokens within the configured expiration', function () {
    config()->set('sanctum.expiration', 60);

    $user = User::factory()->create();
    $token = $user->createToken('integration');

    $this->withToken($token->plainTextToken)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

it('needs the user:read ability to read the token owner', function (array $abilities, int $status) {
    $token = User::factory()->create()->createToken('integration', $abilities);

    $this->withToken($token->plainTextToken)
        ->getJson('/api/user')
        ->assertStatus($status);
})->with([
    'products only' => [['products:read'], 403],
    'user:read' => [['user:read'], 200],
]);
