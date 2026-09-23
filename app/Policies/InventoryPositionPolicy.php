<?php

namespace App\Policies;

use App\Models\InventoryPosition;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class InventoryPositionPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:InventoryPosition');
    }

    public function view(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('View:InventoryPosition') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:InventoryPosition');
    }

    public function update(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('Update:InventoryPosition') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:InventoryPosition');
    }

    public function delete(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('Delete:InventoryPosition') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:InventoryPosition');
    }

    public function restore(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('Restore:InventoryPosition') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:InventoryPosition');
    }

    public function forceDelete(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('ForceDelete:InventoryPosition') && $this->canAccessModelScope($user, $model);
    }
}
