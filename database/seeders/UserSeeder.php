<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        /*
         * The well known demo password is used only outside production: in
         * production the demo accounts get a random password, to be reset by
         * the bootstrap admin before use.
         */
        $password = Hash::make(app()->isProduction() ? Str::random(40) : 'password');
        $demoUserRoles = collect();

        $admins = [
            ['Marco Bianchi', 'marco.bianchi@example.local'],
            ['Sara Ricci', 'sara.ricci@example.local'],
        ];

        foreach ($admins as [$name, $email]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'email_verified_at' => now(),
                    'password' => $password,
                    'remember_token' => Str::random(10),
                ],
            );

            $demoUserRoles->put($user->id, $adminRole->id);
        }

        $regularUsers = [
            ['Luca Ferrari', 'luca.ferrari@example.local'],
            ['Giulia Russo', 'giulia.russo@example.local'],
            ['Andrea Conti', 'andrea.conti@example.local'],
            ['Martina Esposito', 'martina.esposito@example.local'],
            ['Davide Lombardi', 'davide.lombardi@example.local'],
            ['Elena Marinetti', 'elena.marinetti@example.local'],
        ];

        foreach ($regularUsers as [$name, $email]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'email_verified_at' => now(),
                    'password' => $password,
                    'remember_token' => Str::random(10),
                ],
            );

            $demoUserRoles->put($user->id, $userRole->id);
        }

        $scopeIds = DB::table('scopes')->where('is_active', true)->pluck('id');

        // Roles are per scope: the demo users get theirs in every active scope
        // where they have none yet.
        User::query()->whereKey($demoUserRoles->keys())->each(function (User $user) use ($scopeIds, $demoUserRoles): void {
            $missingScopeIds = $scopeIds->diff(
                $user->scopes()->pluck('scopes.id'),
            );
            if ($missingScopeIds->isNotEmpty()) {
                $user->scopes()->attach($missingScopeIds->values()->all());
            }

            foreach ($scopeIds as $scopeId) {
                if ($user->rolesInScope($scopeId)->isEmpty()) {
                    $user->syncRolesInScope($scopeId, [$demoUserRoles[$user->id]]);
                }
            }
        });
    }
}
