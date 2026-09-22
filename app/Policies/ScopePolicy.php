<?php

namespace App\Policies;

use App\Models\Scope;
use App\Models\User;

class ScopePolicy
{
    /*
     * Super admins are allowed through the Shield gate intercept before these
     * methods run: the checks below apply to every other user.
     */

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_scope');
    }

    public function view(User $user, ?Scope $model = null): bool
    {
        return $user->can('view_scope') && $this->belongsTo($user, $model);
    }

    /**
     * Creating scopes is reserved to super admins.
     */
    public function create(User $user): bool
    {
        return $user->isRoot() && $user->can('create_scope');
    }

    public function update(User $user, ?Scope $model = null): bool
    {
        return $user->can('update_scope') && $this->belongsTo($user, $model);
    }

    /**
     * Activating/deactivating a scope is reserved to super admins.
     */
    public function changeStatus(User $user, ?Scope $model = null): bool
    {
        return $user->isRoot() && $this->update($user, $model);
    }

    /**
     * Requesting the deletion of a scope is reserved to super admins.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isRoot() && $user->can('delete_any_scope');
    }

    public function delete(User $user, ?Scope $model = null): bool
    {
        return $user->isRoot() && $user->can('delete_scope');
    }

    public function restoreAny(User $user): bool
    {
        return $user->isRoot() && $user->can('restore_any_scope');
    }

    public function restore(User $user, ?Scope $model = null): bool
    {
        return $user->isRoot() && $user->can('restore_scope');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isRoot() && $user->can('force_delete_any_scope');
    }

    public function forceDelete(User $user, ?Scope $model = null): bool
    {
        return $user->isRoot() && $user->can('force_delete_scope');
    }

    /**
     * Determine whether the user can add or remove members of the given scope.
     */
    public function manageMembership(User $user, Scope $model): bool
    {
        return $user->can('update_user') && $user->hasScope($model->getKey());
    }

    protected function belongsTo(User $user, ?Scope $model): bool
    {
        return $model === null || $user->isRoot() || $user->hasScope($model->getKey());
    }
}
