<?php

namespace Database\Seeders;

use App\Models\Reorder;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use RuntimeException;

class ReorderSeeder extends Seeder
{
    public function __construct(
        private readonly ?int $scopeId = null,
    ) {
    }

    public function run(): void
    {
        if (! is_int($this->scopeId)) {
            throw new RuntimeException('ReorderSeeder requires a scope id.');
        }

        // Apply reorder rules to 12 random stock records
        Stock::query()
            ->where('scope_id', $this->scopeId)
            ->inRandomOrder()
            ->limit(12)
            ->get()
            ->each(function (Stock $stock) {
                Reorder::create([
                    'scope_id'          => $this->scopeId,
                    'stock_id'          => $stock->id,
                    'reorder_point'     => rand(1, 3),
                    'reorder_quantity'  => rand(2, 10),
                    'last_reorder_date' => fake()->optional(0.6)->dateTimeBetween('-6 months', 'now'),
                ]);
            });
    }
}
