<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;

class TagPolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Tag');
    }

    public function view(User $user, ?Tag $model = null): bool
    {
        return $user->can('View:Tag');
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?Tag $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?Tag $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?Tag $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?Tag $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
