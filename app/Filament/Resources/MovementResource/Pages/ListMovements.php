<?php

namespace App\Filament\Resources\MovementResource\Pages;

use App\Filament\Resources\MovementItemResource;
use App\Filament\Resources\MovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMovements extends ListRecords
{
    protected static string $resource = MovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('detailsExport')
                ->translateLabel()
                ->icon('heroicon-o-viewfinder-circle')
                ->label('Details/Export')
                // ->url(fn (): string => MovementItemResource::getUrl()),
                ->url(fn (): string => MovementResource::getUrl('movement-items')),
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
