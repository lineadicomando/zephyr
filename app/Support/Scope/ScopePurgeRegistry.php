<?php

declare(strict_types=1);

namespace App\Support\Scope;

final class ScopePurgeRegistry
{
    /**
     * Tables with a scope_id column that must be purged before deleting scopes.
     *
     * @return list<string>
     */
    public static function tables(): array
    {
        return [
            'inventory_locations',
            'inventory_positions',
            'inventories',
            'movement_items',
            'movement_types',
            'movements',
            'reorder_order_items',
            'reorder_orders',
            'reorders',
            'stocks',
            'tasks',
            // role and permission assignments of the scope (spatie/permission teams)
            'model_has_roles',
            'model_has_permissions',
        ];
    }
}
