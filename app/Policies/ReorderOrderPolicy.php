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
        return $user->can('ViewAny:ReorderOrder');
    }

    public function view(User $user, ?ReorderOrder $model = null): bool
    {
        return $user->can('View:ReorderOrder') && $this->canAccessModelScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:ReorderOrder');
    }

    public function update(User $user, ?ReorderOrder $model = null): bool
    {
        return $user->can('Update:ReorderOrder') && $this->canAccessModelScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:ReorderOrder');
    }

    public function delete(User $user, ?ReorderOrder $model = null): bool
    {
        return $user->can('Delete:ReorderOrder')
            && $this->canAccessModelScope($user, $model)
            && ($model?->status === ReorderOrder::STATUS_DRAFT);
    }

    public function transition(User $user): bool
    {
        return $user->can('Transition:ReorderOrder');
    }
}
