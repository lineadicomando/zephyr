<?php

namespace App\Filament\Resources;

/**
 * Extends FilamentShield's RoleResource to exclude it from Filament's automatic
 * tenant scoping. Roles are global across tenants and the Spatie Role model has
 * no 'scope' relationship, so without this override the global scope registration
 * throws a LogicException when any Role query runs inside a tenanted panel request.
 *
 * The base RoleResource uses BezhanSalleh\PluginEssentials which overrides
 * isScopedToTenant() via a plugin delegation pattern, so overriding the static
 * property alone is insufficient — the method must be overridden directly.
 */
class RoleResource extends \BezhanSalleh\FilamentShield\Resources\Roles\RoleResource
{
    protected static bool $isScopedToTenant = false;

    public static function isScopedToTenant(): bool
    {
        return false;
    }
}
