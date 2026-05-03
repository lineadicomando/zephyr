<?php

namespace App\Policies;

use App\Models\ProductGroup;
use App\Models\User;

class ProductGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product_group');
    }

    public function view(User $user, ProductGroup|null $model = null): bool
    {
        return $user->can('view_product_group');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product_group');
    }

    public function update(User $user, ProductGroup|null $model = null): bool
    {
        return $user->can('update_product_group');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_product_group');
    }

    public function delete(User $user, ProductGroup|null $model = null): bool
    {
        return $user->can('delete_product_group');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_product_group');
    }

    public function restore(User $user, ProductGroup|null $model = null): bool
    {
        return $user->can('restore_product_group');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_product_group');
    }

    public function forceDelete(User $user, ProductGroup|null $model = null): bool
    {
        return $user->can('force_delete_product_group');
    }
}