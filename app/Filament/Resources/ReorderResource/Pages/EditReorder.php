<?php

namespace App\Filament\Resources\ReorderResource\Pages;

use App\Filament\Resources\ReorderResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReorder extends EditRecord
{
    use CancelToCloseAction;

    protected static string $resource = ReorderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
