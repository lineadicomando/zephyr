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
        return $user->can('view_any_inventory_position');
    }

    public function view(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('view_inventory_position') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_inventory_position');
    }

    public function update(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('update_inventory_position') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_inventory_position');
    }

    public function delete(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('delete_inventory_position') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_inventory_position');
    }

    public function restore(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('restore_inventory_position') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_inventory_position');
    }

    public function forceDelete(User $user, ?InventoryPosition $model = null): bool
    {
        return $user->can('force_delete_inventory_position') && $this->canAccessModelScope($user, $model);
    }
}
