<?php

namespace App\Filament\Resources\TaskStatusResource\Pages;

use App\Filament\Resources\TaskStatusResource;
use App\Traits\ViewHeaderAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTaskStatus extends ViewRecord
{
    use ViewHeaderAction;
    protected static string $resource = TaskStatusResource::class;
}
