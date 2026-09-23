<?php

namespace App\Support\Scope;

use App\Models\Scope;
use App\Models\User;

class ScopeAccessResolver
{
    public function isEnforced(): bool
    {
        return true;
    }

    public function userHasAssignedScopes(User $user): bool
    {
        return $user->scopes()->exists();
    }

    public function canAccessScope(User $user, int $scopeId): bool
    {
        if ($user->isRoot()) {
            return true;
        }

        if (! $this->userHasAssignedScopes($user)) {
            return false;
        }

        return $user->hasScope($scopeId);
    }

    /**
     * Whether the user has the permission in the current scope or, outside a
     * scope (API, console), in any of their scopes: roles are per scope.
     */
    public function hasPermissionInContext(User $user, string $permission): bool
    {
        if ($user->can($permission)) {
            return true;
        }

        if ((int) getPermissionsTeamId() !== Scope::GLOBAL_PERMISSIONS_TEAM) {
            return false;
        }

        try {
            foreach ($user->scopes()->pluck('scopes.id') as $scopeId) {
                setPermissionsTeamId($scopeId);
                $user->unsetRelation('roles')->unsetRelation('permissions');

                if ($user->checkPermissionTo($permission)) {
                    return true;
                }
            }
        } finally {
            setPermissionsTeamId(Scope::GLOBAL_PERMISSIONS_TEAM);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }

        return false;
    }

    public function defaultScopeId(): ?int
    {
        $id = Scope::query()->where('slug', 'default')->value('id');

        return is_int($id) ? $id : null;
    }
}
