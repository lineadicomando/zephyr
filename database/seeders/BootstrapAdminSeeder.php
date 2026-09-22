<?php

namespace Database\Seeders;

use App\Models\Scope;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Role;

class BootstrapAdminSeeder extends Seeder
{
    /**
     * Placeholder passwords shipped in the example environment files.
     *
     * @var array<int, string>
     */
    public const PLACEHOLDER_PASSWORDS = ['password', 'change-me-in-production'];

    public function run(): void
    {
        $email = (string) config('app.bootstrap_admin.email');

        $user = User::withTrashed()->where('email', $email)->first()
            ?? $this->createAdmin($email);

        if ($user->trashed()) {
            $user->restore();
        }

        $superAdminRole = Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->first();

        if ($superAdminRole) {
            $user->syncRoles([$superAdminRole]);
        }

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

        $activeScopeIds = Scope::query()
            ->where('is_active', true)
            ->pluck('id');

        $missingScopeIds = $activeScopeIds->diff($user->scopes()->pluck('scopes.id'));
        if ($missingScopeIds->isNotEmpty()) {
            $user->scopes()->attach($missingScopeIds->values()->all());
        }
    }

    /**
     * Create the bootstrap admin. The password is only set on creation so
     * that seeding again never resets the password of an existing admin.
     */
    protected function createAdmin(string $email): User
    {
        $password = (string) config('app.bootstrap_admin.password');

        if ($password === '') {
            throw new RuntimeException('BOOTSTRAP_ADMIN_PASSWORD must be set to create the bootstrap admin.');
        }

        if (app()->isProduction() && in_array($password, self::PLACEHOLDER_PASSWORDS, true)) {
            throw new RuntimeException('BOOTSTRAP_ADMIN_PASSWORD still has a placeholder value: set a real password.');
        }

        return User::query()->forceCreate([
            'name' => (string) config('app.bootstrap_admin.name'),
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }
}
