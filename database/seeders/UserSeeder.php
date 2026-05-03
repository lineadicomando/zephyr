<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole     = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        $userRole      = \Spatie\Permission\Models\Role::where('name', 'user')->first();

        // 2 admins
        User::factory()->create([
            'name'  => 'Marco Bianchi',
            'email' => 'marco.bianchi@example.local',
        ])->assignRole($adminRole);

        User::factory()->create([
            'name'  => 'Sara Ricci',
            'email' => 'sara.ricci@example.local',
        ])->assignRole($adminRole);

        // 6 regular users (IT technicians)
        $users = [
            ['Luca Ferrari',    'luca.ferrari@example.local'],
            ['Giulia Russo',    'giulia.russo@example.local'],
            ['Andrea Conti',    'andrea.conti@example.local'],
            ['Martina Esposito','martina.esposito@example.local'],
            ['Davide Lombardi', 'davide.lombardi@example.local'],
            ['Elena Marinetti', 'elena.marinetti@example.local'],
        ];

        foreach ($users as [$name, $email]) {
            User::factory()->create(['name' => $name, 'email' => $email])->assignRole($userRole);
        }

        $scopeIds = DB::table('scopes')->where('is_active', true)->pluck('id');

        User::query()->each(function (User $user) use ($scopeIds): void {
            $missingScopeIds = $scopeIds->diff($user->scopes()->pluck('scopes.id'));
            if ($missingScopeIds->isNotEmpty()) {
                $user->scopes()->attach($missingScopeIds->values()->all());
            }
        });
    }
}
