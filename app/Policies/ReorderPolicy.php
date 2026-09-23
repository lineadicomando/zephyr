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
        return $user->can('ViewAny:Reorder');
    }

    public function view(User $user, ?Reorder $model = null): bool
    {
        return $user->can('View:Reorder') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Reorder');
    }

    public function update(User $user, ?Reorder $model = null): bool
    {
        return $user->can('Update:Reorder') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Reorder');
    }

    public function delete(User $user, ?Reorder $model = null): bool
    {
        return $user->can('Delete:Reorder') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Reorder');
    }

    public function restore(User $user, ?Reorder $model = null): bool
    {
        return $user->can('Restore:Reorder') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Reorder');
    }

    public function forceDelete(User $user, ?Reorder $model = null): bool
    {
        return $user->can('ForceDelete:Reorder') && $this->canAccessModelScope($user, $model);
    }
}
