<?php

namespace App\Filament\Resources\ReorderResource\Pages;

use App\Filament\Resources\ReorderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReorders extends ListRecords
{
    protected static string $resource = ReorderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
