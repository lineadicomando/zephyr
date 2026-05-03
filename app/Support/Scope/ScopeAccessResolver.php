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

    public function defaultScopeId(): ?int
    {
        $id = Scope::query()->where('slug', 'default')->value('id');

        return is_int($id) ? $id : null;
    }
}
