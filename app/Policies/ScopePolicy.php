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
        return $user->can('ViewAny:Scope');
    }

    public function view(User $user, ?Scope $model = null): bool
    {
        return $user->can('View:Scope') && $this->belongsTo($user, $model);
    }

    /**
     * Creating scopes is reserved to super admins.
     */
    public function create(User $user): bool
    {
        return $user->isRoot() && $user->can('Create:Scope');
    }

    public function update(User $user, ?Scope $model = null): bool
    {
        return $user->can('Update:Scope') && $this->belongsTo($user, $model);
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
        return $user->isRoot() && $user->can('DeleteAny:Scope');
    }

    public function delete(User $user, ?Scope $model = null): bool
    {
        return $user->isRoot() && $user->can('Delete:Scope');
    }

    public function restoreAny(User $user): bool
    {
        return $user->isRoot() && $user->can('RestoreAny:Scope');
    }

    public function restore(User $user, ?Scope $model = null): bool
    {
        return $user->isRoot() && $user->can('Restore:Scope');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isRoot() && $user->can('ForceDeleteAny:Scope');
    }

    public function forceDelete(User $user, ?Scope $model = null): bool
    {
        return $user->isRoot() && $user->can('ForceDelete:Scope');
    }

    /**
     * Determine whether the user can add or remove members of the given scope.
     */
    public function manageMembership(User $user, Scope $model): bool
    {
        return $user->can('Update:User') && $user->hasScope($model->getKey());
    }

    protected function belongsTo(User $user, ?Scope $model): bool
    {
        return $model === null || $user->isRoot() || $user->hasScope($model->getKey());
    }
}
