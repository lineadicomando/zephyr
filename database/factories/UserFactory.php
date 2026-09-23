<?php

namespace Database\Factories;

use App\Models\Scope;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Attach the user to the scope, a new one by default. Users get no scope
     * and no role unless a test asks for them.
     */
    public function inScope(?Scope $scope = null): static
    {
        return $this->afterCreating(fn (User $user) => $user->scopes()->attach($scope ?? Scope::factory()->create()));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public static function setPassword(string $password): void
    {
        static::$password = Hash::make($password);
    }
}
