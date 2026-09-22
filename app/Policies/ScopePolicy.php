<?php

namespace App\Policies;

use App\Models\Scope;
use App\Models\User;

class ScopePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_scope');
    }

    public function view(User $user, ?Scope $model = null): bool
    {
        return $user->can('view_scope');
    }

    public function create(User $user): bool
    {
        return $user->can('create_scope');
    }

    public function update(User $user, ?Scope $model = null): bool
    {
        return $user->can('update_scope');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_scope');
    }

    public function delete(User $user, ?Scope $model = null): bool
    {
        return $user->can('delete_scope');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_scope');
    }

    public function restore(User $user, ?Scope $model = null): bool
    {
        return $user->can('restore_scope');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_scope');
    }

    public function forceDelete(User $user, ?Scope $model = null): bool
    {
        return $user->can('force_delete_scope');
    }

    /**
     * Determine whether the user can add or remove members of the given scope.
     * Super admins are allowed through the Shield gate intercept.
     */
    public function manageMembership(User $user, Scope $model): bool
    {
        return $user->can('update_user') && $user->hasScope($model->getKey());
    }
}
