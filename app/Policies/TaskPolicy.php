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
        return $user->can('ViewAny:Task');
    }

    public function view(User $user, ?Task $model = null): bool
    {
        return $user->can('View:Task') && $this->canAccessModelScope($user, $model) && $this->canAccessTask($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Task');
    }

    public function update(User $user, ?Task $model = null): bool
    {
        return $user->can('Update:Task') && $this->canAccessModelScope($user, $model) && $this->canAccessTask($user, $model);
    }

    /**
     * Detaching a task from an inventory changes the task: it needs the same
     * access as updating it.
     */
    public function detach(User $user, ?Task $model = null): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Only the technician assigned to the task and the admins fill the
     * checklists of its inventories.
     */
    public function fillChecklist(User $user, ?Task $model = null): bool
    {
        return $this->update($user, $model);
    }

    public function detachAny(User $user): bool
    {
        return $user->can('Update:Task');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Task');
    }

    public function delete(User $user, ?Task $model = null): bool
    {
        return $user->can('Delete:Task') && $this->canAccessModelScope($user, $model) && $this->canAccessTask($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Task');
    }

    public function restore(User $user, ?Task $model = null): bool
    {
        return $user->can('Restore:Task') && $this->canAccessModelScope($user, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Task');
    }

    public function forceDelete(User $user, ?Task $model = null): bool
    {
        return $user->can('ForceDelete:Task') && $this->canAccessModelScope($user, $model);
    }

    /**
     * Non admin users can only access the tasks assigned to them.
     */
    protected function canAccessTask(User $user, ?Task $model): bool
    {
        return $model === null || $user->isAdmin() || $model->user_id === $user->id;
    }
}
