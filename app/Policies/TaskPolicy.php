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

    public function view(User $user, ?Task $model = null): bool
    {
        return $user->can('view_task') && $this->canAccessModelScope($user, $model) && $this->canAccessTask($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_task');
    }

    public function update(User $user, ?Task $model = null): bool
    {
        return $user->can('update_task') && $this->canAccessModelScope($user, $model) && $this->canAccessTask($user, $model);
    }

    /**
     * Detaching a task from an inventory changes the task: it needs the same
     * access as updating it.
     */
    public function detach(User $user, ?Task $model = null): bool
    {
        return $this->update($user, $model);
    }

    public function detachAny(User $user): bool
    {
        return $user->can('update_task');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_task');
    }

    public function delete(User $user, ?Task $model = null): bool
    {
        return $user->can('delete_task') && $this->canAccessModelScope($user, $model) && $this->canAccessTask($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_task');
    }

    public function restore(User $user, ?Task $model = null): bool
    {
        return $user->can('restore_task') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_task');
    }

    public function forceDelete(User $user, ?Task $model = null): bool
    {
        return $user->can('force_delete_task') && $this->canAccessModelScope($user, $model);
    }

    /**
     * Non admin users can only access the tasks assigned to them.
     */
    protected function canAccessTask(User $user, ?Task $model): bool
    {
        return $model === null || $user->isAdmin() || $model->user_id === $user->id;
    }
}
