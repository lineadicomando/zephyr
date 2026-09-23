<?php

namespace App\Policies;

use App\Models\MovementType;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class MovementTypePolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_movement_type');
    }

    public function view(User $user, ?MovementType $model = null): bool
    {
        return $user->can('view_movement_type') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_movement_type');
    }

    public function update(User $user, ?MovementType $model = null): bool
    {
        return $user->can('update_movement_type') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_movement_type');
    }

    public function delete(User $user, ?MovementType $model = null): bool
    {
        return $user->can('delete_movement_type') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_movement_type');
    }

    public function restore(User $user, ?MovementType $model = null): bool
    {
        return $user->can('restore_movement_type') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_movement_type');
    }

    public function forceDelete(User $user, ?MovementType $model = null): bool
    {
        return $user->can('force_delete_movement_type') && $this->canAccessModelScope($user, $model);
    }
}
