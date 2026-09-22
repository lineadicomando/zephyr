<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Traits\CancelToCloseAction;
use App\Traits\EditFirstRedirectUrlOverride;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    use CancelToCloseAction, EditFirstRedirectUrlOverride;

    protected static string $resource = TaskResource::class;
}
