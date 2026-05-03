<?php

namespace Database\Factories;

use App\Models\Scope;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $user->assignRole($role);

            $defaultScope = Scope::query()->firstOrCreate(
                ['slug' => 'default'],
                [
                    'name' => 'Default',
                    'type' => 'company',
                    'is_active' => true,
                ],
            );

            if (! $user->scopes()->whereKey($defaultScope->id)->exists()) {
                $user->scopes()->attach($defaultScope->id);
            }
        });
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
