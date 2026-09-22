<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Traits\CancelToCloseAction;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use CancelToCloseAction;

    protected static string $resource = UserResource::class;

    /**
     * New users join the current scope. Users created by an admin get the
     * "user" role, since only super admins can assign roles.
     */
    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();

        if ($tenant = filament()->getTenant()) {
            $user->scopes()->syncWithoutDetaching([$tenant->getKey()]);
        }

        if (! auth()->user()?->isRoot()) {
            $user->syncRoles(['user']);
        }
    }
}
