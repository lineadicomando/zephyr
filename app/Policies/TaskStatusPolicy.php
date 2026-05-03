<?php

namespace App\Policies;

use App\Models\TaskStatus;
use App\Models\User;

class TaskStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task_status');
    }

    public function view(User $user, TaskStatus|null $model = null): bool
    {
        return $user->can('view_task_status');
    }

    public function create(User $user): bool
    {
        return $user->can('create_task_status');
    }

    public function update(User $user, TaskStatus|null $model = null): bool
    {
        return $user->can('update_task_status');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_task_status');
    }

    public function delete(User $user, TaskStatus|null $model = null): bool
    {
        return $user->can('delete_task_status');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_task_status');
    }

    public function restore(User $user, TaskStatus|null $model = null): bool
    {
        return $user->can('restore_task_status');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_task_status');
    }

    public function forceDelete(User $user, TaskStatus|null $model = null): bool
    {
        return $user->can('force_delete_task_status');
    }
}