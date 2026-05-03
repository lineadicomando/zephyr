<?php

namespace App\Policies;

use App\Models\Reorder;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class ReorderPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_reorder');
    }

    public function view(User $user, Reorder|null $model = null): bool
    {
        return $user->can('view_reorder') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_reorder');
    }

    public function update(User $user, Reorder|null $model = null): bool
    {
        return $user->can('update_reorder') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_reorder');
    }

    public function delete(User $user, Reorder|null $model = null): bool
    {
        return $user->can('delete_reorder') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_reorder');
    }

    public function restore(User $user, Reorder|null $model = null): bool
    {
        return $user->can('restore_reorder') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_reorder');
    }

    public function forceDelete(User $user, Reorder|null $model = null): bool
    {
        return $user->can('force_delete_reorder') && $this->canAccessModelScope($user, $model);
    }
}
