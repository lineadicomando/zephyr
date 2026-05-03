<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Traits\ViewHeaderAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewTask extends ViewRecord
{
    use ViewHeaderAction;
    protected static string $resource = TaskResource::class;
}
