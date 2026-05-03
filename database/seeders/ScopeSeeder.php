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

            DB::table('scopes')->updateOrInsert(
                ['slug' => $scope['slug']],
                [
                    'name' => $scope['name'],
                    'type' => $scope['type'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    /**
     * @return list<array{slug:string,name:string,type:string}>
     */
    protected function scopes(): array
    {
        return [
            ['slug' => 'default', 'name' => 'Default', 'type' => 'company'],
            ['slug' => 'demo-school', 'name' => 'Demo School', 'type' => 'school'],
            ['slug' => 'demo-branch', 'name' => 'Demo Branch', 'type' => 'branch'],
        ];
    }
}
