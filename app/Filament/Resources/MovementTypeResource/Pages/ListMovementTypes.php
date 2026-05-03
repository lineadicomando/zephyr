<?php

namespace App\Filament\Resources\MovementTypeResource\Pages;

use App\Filament\Resources\MovementTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMovementTypes extends ListRecords
{
    protected static string $resource = MovementTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
