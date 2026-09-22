<?php

namespace Database\Seeders;

class DemoScopeSeeder extends ScopeSeeder
{
    /**
     * @return list<array{slug:string,name:string,type:string}>
     */
    protected function scopes(): array
    {
        return [
            ['slug' => 'demo-school', 'name' => 'Demo School', 'type' => 'school'],
            ['slug' => 'demo-branch', 'name' => 'Demo Branch', 'type' => 'branch'],
        ];
    }
}
