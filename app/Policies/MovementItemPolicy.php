<?php

namespace App\Policies;

use App\Models\MovementItem;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class MovementItemPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:MovementItem');
    }

    public function view(User $user, ?MovementItem $model = null): bool
    {
        return $user->can('View:MovementItem') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:MovementItem');
    }

    public function update(User $user, ?MovementItem $model = null): bool
    {
        return $user->can('Update:MovementItem') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:MovementItem');
    }

    public function delete(User $user, ?MovementItem $model = null): bool
    {
        return $user->can('Delete:MovementItem') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:MovementItem');
    }

    public function restore(User $user, ?MovementItem $model = null): bool
    {
        return $user->can('Restore:MovementItem') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:MovementItem');
    }

    public function forceDelete(User $user, ?MovementItem $model = null): bool
    {
        return $user->can('ForceDelete:MovementItem') && $this->canAccessModelScope($user, $model);
    }
}
