<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ScopeSeeder::class,
            RolesAndPermissionsSeeder::class,
            BootstrapAdminSeeder::class,
        ]);

        if (config('app.seed_demo_data')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
