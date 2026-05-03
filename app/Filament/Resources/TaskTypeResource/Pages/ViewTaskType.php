<?php

namespace App\Filament\Resources\TaskTypeResource\Pages;

use App\Filament\Resources\TaskTypeResource;
use App\Traits\ViewHeaderAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTaskType extends ViewRecord
{
    use ViewHeaderAction;
    protected static string $resource = TaskTypeResource::class;
}
