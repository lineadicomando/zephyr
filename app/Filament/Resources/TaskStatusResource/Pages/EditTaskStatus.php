<?php

namespace App\Filament\Resources\TaskStatusResource\Pages;

use App\Filament\Resources\TaskStatusResource;
use App\Models\TaskStatus;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskStatus extends EditRecord
{
    use CancelToCloseAction;
    protected static string $resource = TaskStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->hidden(fn (TaskStatus $record) => $record->hasRelated()),
        ];
    }
}
