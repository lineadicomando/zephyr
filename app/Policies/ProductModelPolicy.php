<?php

namespace App\Policies;

use App\Models\ProductModel;
use App\Models\User;

class ProductModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product_model');
    }

    public function view(User $user, ProductModel|null $model = null): bool
    {
        return $user->can('view_product_model');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product_model');
    }

    public function update(User $user, ProductModel|null $model = null): bool
    {
        return $user->can('update_product_model');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_product_model');
    }

    public function delete(User $user, ProductModel|null $model = null): bool
    {
        return $user->can('delete_product_model');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_product_model');
    }

    public function restore(User $user, ProductModel|null $model = null): bool
    {
        return $user->can('restore_product_model');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_product_model');
    }

    public function forceDelete(User $user, ProductModel|null $model = null): bool
    {
        return $user->can('force_delete_product_model');
    }
}