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
        return $user->can('view_any_inventory_location');
    }

    public function view(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('view_inventory_location') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_inventory_location');
    }

    public function update(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('update_inventory_location') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_inventory_location');
    }

    public function delete(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('delete_inventory_location') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_inventory_location');
    }

    public function restore(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('restore_inventory_location') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_inventory_location');
    }

    public function forceDelete(User $user, ?InventoryLocation $model = null): bool
    {
        return $user->can('force_delete_inventory_location') && $this->canAccessModelScope($user, $model);
    }
}
