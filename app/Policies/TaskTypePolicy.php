<?php

namespace App\Policies;

use App\Models\TaskType;
use App\Models\User;

class TaskTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task_type');
    }

    public function view(User $user, TaskType|null $model = null): bool
    {
        return $user->can('view_task_type');
    }

    public function create(User $user): bool
    {
        return $user->can('create_task_type');
    }

    public function update(User $user, TaskType|null $model = null): bool
    {
        return $user->can('update_task_type');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_task_type');
    }

    public function delete(User $user, TaskType|null $model = null): bool
    {
        return $user->can('delete_task_type');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_task_type');
    }

    public function restore(User $user, TaskType|null $model = null): bool
    {
        return $user->can('restore_task_type');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_task_type');
    }

    public function forceDelete(User $user, TaskType|null $model = null): bool
    {
        return $user->can('force_delete_task_type');
    }
}