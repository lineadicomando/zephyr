<?php

namespace App\Policies;

use App\Models\ProductGroup;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;

class ProductGroupPolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:ProductGroup');
    }

    public function view(User $user, ?ProductGroup $model = null): bool
    {
        return $user->can('View:ProductGroup');
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?ProductGroup $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?ProductGroup $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?ProductGroup $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?ProductGroup $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
