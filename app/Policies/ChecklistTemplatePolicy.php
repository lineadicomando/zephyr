<?php

namespace App\Policies;

use App\Models\ChecklistTemplate;
use App\Models\User;
use App\Policies\Concerns\RestrictsGlobalCatalogChanges;

class ChecklistTemplatePolicy
{
    use RestrictsGlobalCatalogChanges;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:ChecklistTemplate');
    }

    public function view(User $user, ?ChecklistTemplate $model = null): bool
    {
        return $user->can('View:ChecklistTemplate');
    }

    public function create(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function update(User $user, ?ChecklistTemplate $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function delete(User $user, ?ChecklistTemplate $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function restore(User $user, ?ChecklistTemplate $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }

    public function forceDelete(User $user, ?ChecklistTemplate $model = null): bool
    {
        return $this->canChangeGlobalCatalog($user);
    }
}
