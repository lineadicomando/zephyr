<?php

namespace App\Policies;

use App\Models\Movement;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class MovementPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Movement');
    }

    public function view(User $user, ?Movement $model = null): bool
    {
        return $user->can('View:Movement') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Movement');
    }

    public function update(User $user, ?Movement $model = null): bool
    {
        return $user->can('Update:Movement') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Movement');
    }

    public function delete(User $user, ?Movement $model = null): bool
    {
        return $user->can('Delete:Movement') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Movement');
    }

    public function restore(User $user, ?Movement $model = null): bool
    {
        return $user->can('Restore:Movement') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Movement');
    }

    public function forceDelete(User $user, ?Movement $model = null): bool
    {
        return $user->can('ForceDelete:Movement') && $this->canAccessModelScope($user, $model);
    }
}
