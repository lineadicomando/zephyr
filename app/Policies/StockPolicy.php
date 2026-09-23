<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class StockPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Stock');
    }

    public function view(User $user, ?Stock $model = null): bool
    {
        return $user->can('View:Stock') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Stock');
    }

    public function update(User $user, ?Stock $model = null): bool
    {
        return $user->can('Update:Stock') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Stock');
    }

    public function delete(User $user, ?Stock $model = null): bool
    {
        return $user->can('Delete:Stock') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Stock');
    }

    public function restore(User $user, ?Stock $model = null): bool
    {
        return $user->can('Restore:Stock') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Stock');
    }

    public function forceDelete(User $user, ?Stock $model = null): bool
    {
        return $user->can('ForceDelete:Stock') && $this->canAccessModelScope($user, $model);
    }
}
