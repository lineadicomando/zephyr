<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Models\Stock;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\StockResource;
use App\Filament\Resources\InventoryResource;

// use pxlrbt\FilamentExcel\Exports\ExcelExport;
// use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
// use Maatwebsite\Excel\Excel;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ExportAction::make('table')
            //     ->translateLabel()
            //     ->exports([
            //         ExcelExport::make()
            //             ->fromTable()
            //             ->askForFilename(now()->format('Y-m-d') . '_' . __('Inventory'))
            //             ->askForWriterType(Excel::XLSX)
            //     ]),
            Actions\Action::make("stocks")
                ->translateLabel()
                ->hidden(fn() => !auth()->user()->can("view", Stock::class))
                ->icon("heroicon-o-queue-list")
                ->label("Export")
                ->url(fn(): string => InventoryResource::getUrl("stocks")),
            Actions\CreateAction::make()->icon("heroicon-o-plus"),
        ];
    }
}
