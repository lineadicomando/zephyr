<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use App\Models\Inventory;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\InventoryResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('inventory')
                ->translateLabel()
                ->hidden(fn () => !auth()->user()->can('view', Inventory::class))
                ->icon('heroicon-o-archive-box')
                ->label('Inventory')
                ->url(fn (): string => InventoryResource::getUrl()),
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
