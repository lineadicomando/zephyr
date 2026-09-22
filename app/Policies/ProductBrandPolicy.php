<?php

namespace App\Policies;

use App\Models\ProductBrand;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;

class ProductBrandPolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product_brand');
    }

    public function view(User $user, ?ProductBrand $model = null): bool
    {
        return $user->can('view_product_brand');
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?ProductBrand $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?ProductBrand $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?ProductBrand $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?ProductBrand $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
