<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user');
    }

    public function view(User $user, ?User $model = null): bool
    {
        return $user->can('view_user');
    }

    public function create(User $user): bool
    {
        return $user->can('create_user');
    }

    public function update(User $user, ?User $model = null): bool
    {
        return $user->can('update_user') && $this->canManageAccount($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_user');
    }

    public function delete(User $user, ?User $model = null): bool
    {
        return $user->can('delete_user') && $this->canManageAccount($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_user');
    }

    public function restore(User $user, ?User $model = null): bool
    {
        return $user->can('restore_user') && $this->canManageAccount($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_user');
    }

    public function forceDelete(User $user, ?User $model = null): bool
    {
        return $user->can('force_delete_user') && $this->canManageAccount($user, $model);
    }

    /**
     * Super admin accounts can only be managed by other super admins.
     */
    protected function canManageAccount(User $user, ?User $model): bool
    {
        return $model === null || ! $model->isRoot() || $user->isRoot();
    }
}
