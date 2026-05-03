<?php

namespace App\Filament\Resources\InventoryPositionResource\Pages;

use App\Filament\Resources\InventoryPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryPositions extends ListRecords
{
    protected static string $resource = InventoryPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
