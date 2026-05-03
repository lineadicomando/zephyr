<?php

namespace Database\Seeders;

use App\Models\Scope;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class BootstrapAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('BOOTSTRAP_ADMIN_EMAIL', 'admin@cmdln.it');
        $name = (string) env('BOOTSTRAP_ADMIN_NAME', 'Admin');
        $password = (string) env('BOOTSTRAP_ADMIN_PASSWORD', 'password');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ],
        );

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
}
