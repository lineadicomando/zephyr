<?php

namespace App\Policies;

use App\Models\ProductBrand;
use App\Models\User;

class ProductBrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product_brand');
    }

    public function view(User $user, ProductBrand|null $model = null): bool
    {
        return $user->can('view_product_brand');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product_brand');
    }

    public function update(User $user, ProductBrand|null $model = null): bool
    {
        return $user->can('update_product_brand');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_product_brand');
    }

    public function delete(User $user, ProductBrand|null $model = null): bool
    {
        return $user->can('delete_product_brand');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_product_brand');
    }

    public function restore(User $user, ProductBrand|null $model = null): bool
    {
        return $user->can('restore_product_brand');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_product_brand');
    }

    public function forceDelete(User $user, ProductBrand|null $model = null): bool
    {
        return $user->can('force_delete_product_brand');
    }
}