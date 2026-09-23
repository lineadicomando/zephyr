<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Traits\CancelToCloseAction;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    use CancelToCloseAction;

    protected static string $resource = UserResource::class;

    /**
     * New users join the current scope. Users created by an admin get the
     * "user" role in it, since only super admins can assign roles.
     */
    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();
        $tenant = filament()->getTenant();

        $user->scopes()->syncWithoutDetaching([$tenant->getKey()]);

        if (! auth()->user()?->isRoot()) {
            $user->syncRolesInScope($tenant->getKey(), [Role::findByName('user', 'web')->getKey()]);
        }
    }
}
