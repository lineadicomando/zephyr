<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use App\Traits\CancelToCloseAction;
use Filament\Resources\Pages\CreateRecord;

class CreateChecklistTemplate extends CreateRecord
{
    use CancelToCloseAction;

    protected static string $resource = ChecklistTemplateResource::class;
}
