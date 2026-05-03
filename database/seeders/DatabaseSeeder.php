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

        $seedDemoData = filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL);

        if ($seedDemoData) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
