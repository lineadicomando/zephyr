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
        return $user->can('ViewAny:Inventory');
    }

    public function view(User $user, ?Inventory $model = null): bool
    {
        return $user->can('View:Inventory') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Inventory');
    }

    public function update(User $user, ?Inventory $model = null): bool
    {
        return $user->can('Update:Inventory') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Inventory');
    }

    public function delete(User $user, ?Inventory $model = null): bool
    {
        return $user->can('Delete:Inventory') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Inventory');
    }

    public function restore(User $user, ?Inventory $model = null): bool
    {
        return $user->can('Restore:Inventory') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Inventory');
    }

    public function forceDelete(User $user, ?Inventory $model = null): bool
    {
        return $user->can('ForceDelete:Inventory') && $this->canAccessModelScope($user, $model);
    }
}
