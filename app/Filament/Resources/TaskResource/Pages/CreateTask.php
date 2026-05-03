<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\TaskResource\Pages;
use App\Traits\EditFirstRedirectUrlOverride;

class CreateTask extends CreateRecord
{

    use CancelToCloseAction, EditFirstRedirectUrlOverride;
    protected static string $resource = TaskResource::class;
}
