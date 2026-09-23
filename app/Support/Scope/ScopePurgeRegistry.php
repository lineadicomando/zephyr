<?php

declare(strict_types=1);

namespace App\Support\Scope;

final class ScopePurgeRegistry
{
    /**
     * Tables with a scope_id column that must be purged before deleting scopes,
     * in foreign key order: the referencing tables come first.
     *
     * @return list<string>
     */
    public static function tables(): array
    {
        return [
            'reorder_order_items',
            'reorder_orders',
            'reorders',
            'movement_items',
            'movements',
            'movement_types',
            'stocks',
            'tasks',
            'inventories',
            'inventory_positions',
            'inventory_locations',
            // role and permission assignments of the scope (spatie/permission teams)
            'model_has_roles',
            'model_has_permissions',
        ];
    }
}
