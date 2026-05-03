<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\Scope\ScopeAccessResolver;

trait ChecksScopeAccess
{
    protected function canAccessModelScope(User $user, mixed $model): bool
    {
        if ($user->isRoot()) {
            return true;
        }

        if ($model === null) {
            return true;
        }

        $scopeId = $model->scope_id ?? null;

        if (! is_int($scopeId) && ! ctype_digit((string) $scopeId)) {
            return false;
        }

        return app(ScopeAccessResolver::class)->canAccessScope($user, (int) $scopeId);
    }
}
