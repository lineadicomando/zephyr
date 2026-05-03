<?php

namespace App\Filament\Resources\TaskTypeResource\Pages;

use App\Filament\Resources\TaskTypeResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskType extends CreateRecord
{
    use CancelToCloseAction;
    protected static string $resource = TaskTypeResource::class;
}
