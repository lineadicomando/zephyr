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
        $demoUserIds = collect();

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

            $demoUserIds->push($user->id);

            if (! $user->hasRole($adminRole)) {
                $user->assignRole($adminRole);
            }
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

            $demoUserIds->push($user->id);

            if (! $user->hasRole($userRole)) {
                $user->assignRole($userRole);
            }
        }

        $scopeIds = DB::table('scopes')->where('is_active', true)->pluck('id');

        User::query()->whereKey($demoUserIds)->each(function (User $user) use ($scopeIds): void {
            $missingScopeIds = $scopeIds->diff(
                $user->scopes()->pluck('scopes.id'),
            );
            if ($missingScopeIds->isNotEmpty()) {
                $user->scopes()->attach($missingScopeIds->values()->all());
            }
        });
    }
}
