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
        return $user->can('view_any_movement_item');
    }

    public function view(User $user, MovementItem|null $model = null): bool
    {
        return $user->can('view_movement_item') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_movement_item');
    }

    public function update(User $user, MovementItem|null $model = null): bool
    {
        return $user->can('update_movement_item') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_movement_item');
    }

    public function delete(User $user, MovementItem|null $model = null): bool
    {
        return $user->can('delete_movement_item') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_movement_item');
    }

    public function restore(User $user, MovementItem|null $model = null): bool
    {
        return $user->can('restore_movement_item') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_movement_item');
    }

    public function forceDelete(User $user, MovementItem|null $model = null): bool
    {
        return $user->can('force_delete_movement_item') && $this->canAccessModelScope($user, $model);
    }
}
