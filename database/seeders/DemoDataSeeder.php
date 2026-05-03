<?php

namespace Database\Seeders;

use App\Models\Scope;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductCatalogSeeder::class,
            UserSeeder::class,
        ]);

        $scopeIds = Scope::query()
            ->whereIn('slug', ['default', 'demo-school', 'demo-branch'])
            ->where('is_active', true)
            ->pluck('id');

        foreach ($scopeIds as $scopeId) {
            (new LocationSeeder((int) $scopeId))->run();
            (new InventorySeeder((int) $scopeId))->run();
            (new MovementSeeder((int) $scopeId))->run();
            (new TaskSeeder((int) $scopeId))->run();
            (new ReorderSeeder((int) $scopeId))->run();
        }
    }
}
