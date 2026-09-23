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
        return $user->can('ViewAny:MovementType');
    }

    public function view(User $user, ?MovementType $model = null): bool
    {
        return $user->can('View:MovementType') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:MovementType');
    }

    public function update(User $user, ?MovementType $model = null): bool
    {
        return $user->can('Update:MovementType') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:MovementType');
    }

    public function delete(User $user, ?MovementType $model = null): bool
    {
        return $user->can('Delete:MovementType') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:MovementType');
    }

    public function restore(User $user, ?MovementType $model = null): bool
    {
        return $user->can('Restore:MovementType') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:MovementType');
    }

    public function forceDelete(User $user, ?MovementType $model = null): bool
    {
        return $user->can('ForceDelete:MovementType') && $this->canAccessModelScope($user, $model);
    }
}
