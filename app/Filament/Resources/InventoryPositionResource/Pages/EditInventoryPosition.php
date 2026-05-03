<?php

namespace App\Filament\Resources\InventoryPositionResource\Pages;

use App\Filament\Resources\InventoryPositionResource;
use App\Models\InventoryPosition;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryPosition extends EditRecord
{
    use CancelToCloseAction;
    protected static string $resource = InventoryPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->hidden(fn (InventoryPosition $record) => $record->hasRelated()),
        ];
    }
}
