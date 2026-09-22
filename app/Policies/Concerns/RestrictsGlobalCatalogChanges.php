<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * The global catalog is shared by every scope: only super admins may change it.
 */
trait RestrictsGlobalCatalogChanges
{
    protected function canChangeGlobalCatalog(User $user): bool
    {
        return $user->isRoot();
    }
}
