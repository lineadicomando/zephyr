<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class InventoryPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_inventory');
    }

    public function view(User $user, Inventory|null $model = null): bool
    {
        return $user->can('view_inventory') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_inventory');
    }

    public function update(User $user, Inventory|null $model = null): bool
    {
        return $user->can('update_inventory') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_inventory');
    }

    public function delete(User $user, Inventory|null $model = null): bool
    {
        return $user->can('delete_inventory') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_inventory');
    }

    public function restore(User $user, Inventory|null $model = null): bool
    {
        return $user->can('restore_inventory') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_inventory');
    }

    public function forceDelete(User $user, Inventory|null $model = null): bool
    {
        return $user->can('force_delete_inventory') && $this->canAccessModelScope($user, $model);
    }
}
