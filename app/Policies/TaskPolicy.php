<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class TaskPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task');
    }

    public function view(User $user, Task|null $model = null): bool
    {
        return $user->can('view_task') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_task');
    }

    public function update(User $user, Task|null $model = null): bool
    {
        return $user->can('update_task') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_task');
    }

    public function delete(User $user, Task|null $model = null): bool
    {
        return $user->can('delete_task') && $this->canAccessModelScope($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_task');
    }

    public function restore(User $user, Task|null $model = null): bool
    {
        return $user->can('restore_task') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_task');
    }

    public function forceDelete(User $user, Task|null $model = null): bool
    {
        return $user->can('force_delete_task') && $this->canAccessModelScope($user, $model);
    }
}
