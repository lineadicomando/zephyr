<?php

namespace App\Filament\Resources\InventoryLocationResource\Pages;

use App\Filament\Resources\InventoryLocationResource;
use App\Models\InventoryLocation;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryLocation extends EditRecord
{
    use CancelToCloseAction;

    protected static string $resource = InventoryLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->hidden(fn (InventoryLocation $record) => $record->hasRelated()),
            // Actions\DeleteAction::make(),
        ];
    }
}
