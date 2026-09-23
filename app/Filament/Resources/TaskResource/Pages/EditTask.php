<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Actions\RescheduleTaskAction;
use App\Filament\Resources\TaskResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    use CancelToCloseAction;

    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RescheduleTaskAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
