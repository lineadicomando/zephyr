<?php

namespace App\Policies;

use App\Models\ProductType;
use App\Models\User;

class ProductTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product_type');
    }

    public function view(User $user, ProductType|null $model = null): bool
    {
        return $user->can('view_product_type');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product_type');
    }

    public function update(User $user, ProductType|null $model = null): bool
    {
        return $user->can('update_product_type');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_product_type');
    }

    public function delete(User $user, ProductType|null $model = null): bool
    {
        return $user->can('delete_product_type');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_product_type');
    }

    public function restore(User $user, ProductType|null $model = null): bool
    {
        return $user->can('restore_product_type');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_product_type');
    }

    public function forceDelete(User $user, ProductType|null $model = null): bool
    {
        return $user->can('force_delete_product_type');
    }
}