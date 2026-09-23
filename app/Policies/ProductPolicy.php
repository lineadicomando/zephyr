<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;
use App\Support\Scope\ScopeAccessResolver;

class ProductPolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return app(ScopeAccessResolver::class)->hasPermissionInContext($user, 'view_any_product')
            && app(ScopeAccessResolver::class)->userHasAssignedScopes($user);
    }

    public function view(User $user, ?Product $model = null): bool
    {
        return app(ScopeAccessResolver::class)->hasPermissionInContext($user, 'view_product')
            && app(ScopeAccessResolver::class)->userHasAssignedScopes($user);
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?Product $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?Product $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?Product $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?Product $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
