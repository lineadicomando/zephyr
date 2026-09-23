<?php

namespace App\Policies;

use App\Models\InventoryLocation;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class InventoryLocationPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:InventoryLocation');
    }

    public function view(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('View:InventoryLocation') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:InventoryLocation');
    }

    public function update(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('Update:InventoryLocation') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:InventoryLocation');
    }

    public function delete(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('Delete:InventoryLocation') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:InventoryLocation');
    }

    public function restore(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('Restore:InventoryLocation') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:InventoryLocation');
    }

    public function forceDelete(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('ForceDelete:InventoryLocation') && $this->canAccessModelScope($user, $model);
    }
}
