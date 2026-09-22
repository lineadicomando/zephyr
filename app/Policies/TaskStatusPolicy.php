<?php

namespace App\Policies;

use App\Models\TaskStatus;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;

class TaskStatusPolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task_status');
    }

    public function view(User $user, ?TaskStatus $model = null): bool
    {
        return $user->can('view_task_status');
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?TaskStatus $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?TaskStatus $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?TaskStatus $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?TaskStatus $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
