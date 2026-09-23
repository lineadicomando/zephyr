<?php

namespace App\Policies;

use App\Models\ProductModel;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;

class ProductModelPolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:ProductModel');
    }

    public function view(User $user, ?ProductModel $model = null): bool
    {
        return $user->can('View:ProductModel');
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?ProductModel $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?ProductModel $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?ProductModel $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?ProductModel $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
