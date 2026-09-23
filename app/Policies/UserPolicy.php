<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /*
     * Super admins are allowed through the Shield gate intercept before these
     * methods run: the checks below apply to every other user.
     */

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:User');
    }

    public function view(User $user, ?User $model = null): bool
    {
        return $user->can('View:User') && $this->canSeeAccount($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:User');
    }

    public function update(User $user, ?User $model = null): bool
    {
        return $user->can('Update:User')
            && ($model?->is($user) || $this->canManageAccount($user, $model));
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:User');
    }

    public function delete(User $user, ?User $model = null): bool
    {
        return $user->can('Delete:User') && $this->canManageAccount($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:User');
    }

    public function restore(User $user, ?User $model = null): bool
    {
        return $user->can('Restore:User') && $this->canManageAccount($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:User');
    }

    public function forceDelete(User $user, ?User $model = null): bool
    {
        return $user->can('ForceDelete:User') && $this->canManageAccount($user, $model);
    }

    /**
     * Users can see their own account and the accounts sharing a scope with them.
     */
    protected function canSeeAccount(User $user, ?User $model): bool
    {
        return $model === null
            || $user->isRoot()
            || $model->is($user)
            || $user->sharesScopeWith($model);
    }

    /**
     * Accounts that are admin or super admin in any scope are managed only by
     * super admins; other accounts by the users belonging to every scope of
     * the account, so that an admin cannot take over an account used in
     * scopes they cannot see. Nobody but a super admin can manage their own
     * account through these abilities.
     */
    protected function canManageAccount(User $user, ?User $model): bool
    {
        if ($model === null || $user->isRoot()) {
            return true;
        }

        return ! $model->is($user)
            && ! $model->isAdminInAnyScope()
            && $user->coversScopesOf($model);
    }
}
