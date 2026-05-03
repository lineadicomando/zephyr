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
        return $user->can('view_any_stock');
    }

    public function view(User $user, Stock|null $model = null): bool
    {
        return $user->can('view_stock') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_stock');
    }

    public function update(User $user, Stock|null $model = null): bool
    {
        return $user->can('update_stock') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_stock');
    }

    public function delete(User $user, Stock|null $model = null): bool
    {
        return $user->can('delete_stock') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_stock');
    }

    public function restore(User $user, Stock|null $model = null): bool
    {
        return $user->can('restore_stock') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_stock');
    }

    public function forceDelete(User $user, Stock|null $model = null): bool
    {
        return $user->can('force_delete_stock') && $this->canAccessModelScope($user, $model);
    }
}
