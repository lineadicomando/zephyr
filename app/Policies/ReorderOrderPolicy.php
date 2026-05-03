<?php

namespace App\Policies;

use App\Models\ReorderOrder;
use App\Models\User;
use App\Policies\Concerns\ChecksScopeAccess;

class ReorderOrderPolicy
{
    use ChecksScopeAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_reorder_order');
    }

    public function view(User $user, ReorderOrder|null $model = null): bool
    {
        return $user->can('view_reorder_order') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_reorder_order');
    }

    public function update(User $user, ReorderOrder|null $model = null): bool
    {
        return $user->can('update_reorder_order') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_reorder_order');
    }

    public function delete(User $user, ReorderOrder|null $model = null): bool
    {
        return $user->can('delete_reorder_order')
            && $this->canAccessModelScope($user, $model)
            && ($model?->status === ReorderOrder::STATUS_DRAFT);
    }

    public function transition(User $user): bool
    {
        return $user->can('transition_reorder_order');
    }
}
