<?php

namespace App\Policies;

use App\Models\Movement;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class MovementPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_movement');
    }

    public function view(User $user, Movement|null $model = null): bool
    {
        return $user->can('view_movement') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_movement');
    }

    public function update(User $user, Movement|null $model = null): bool
    {
        return $user->can('update_movement') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_movement');
    }

    public function delete(User $user, Movement|null $model = null): bool
    {
        return $user->can('delete_movement') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_movement');
    }

    public function restore(User $user, Movement|null $model = null): bool
    {
        return $user->can('restore_movement') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_movement');
    }

    public function forceDelete(User $user, Movement|null $model = null): bool
    {
        return $user->can('force_delete_movement') && $this->canAccessModelScope($user, $model);
    }
}
