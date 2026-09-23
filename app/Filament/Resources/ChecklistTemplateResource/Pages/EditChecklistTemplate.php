<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use App\Models\ChecklistTemplate;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChecklistTemplate extends EditRecord
{
    use CancelToCloseAction;

    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->hidden(fn (ChecklistTemplate $record) => $record->hasRelated()),
        ];
    }
}
