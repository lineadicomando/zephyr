<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use App\Traits\ViewHeaderAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChecklistTemplate extends ViewRecord
{
    use ViewHeaderAction;

    protected static string $resource = ChecklistTemplateResource::class;
}
