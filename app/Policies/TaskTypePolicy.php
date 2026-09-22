<?php

namespace App\Policies;

use App\Models\TaskType;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;

class TaskTypePolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task_type');
    }

    public function view(User $user, ?TaskType $model = null): bool
    {
        return $user->can('view_task_type');
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?TaskType $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?TaskType $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?TaskType $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?TaskType $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
