<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScopeSeeder extends Seeder
{
    /** @var list<string> */
    private const ALLOWED_TYPES = [
        'company',
        'school',
        'branch',
        'team',
        'department',
        'other',
    ];

    public function run(): void
    {
        foreach ($this->scopes() as $scope) {
            if (! in_array($scope['type'], self::ALLOWED_TYPES, true)) {
                throw new InvalidArgumentException("Unsupported scope type [{$scope['type']}] for slug [{$scope['slug']}].");
            }

            // Existing scopes are left untouched: seeding again must not
            // reactivate a scope pending deletion or rewrite its dates.
            if (DB::table('scopes')->where('slug', $scope['slug'])->exists()) {
                continue;
            }

            DB::table('scopes')->insert([
                'slug' => $scope['slug'],
                'name' => $scope['name'],
                'type' => $scope['type'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return list<array{slug:string,name:string,type:string}>
     */
    protected function scopes(): array
    {
        return [
            ['slug' => 'default', 'name' => 'Default', 'type' => 'company'],
        ];
    }
}
