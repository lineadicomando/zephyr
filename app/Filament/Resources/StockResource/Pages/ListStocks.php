<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\StockResource;
use App\Models\Inventory;
use App\Support\Export\ExportFilename;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('inventory')
                ->translateLabel()
                ->label('Inventory')
                ->hidden(fn () => ! auth()->user()->can('view', Inventory::class))
                ->icon('heroicon-o-archive-box')
                ->url(fn (): string => InventoryResource::getUrl()),
            ExportAction::make('table')
                ->translateLabel()
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->askForFilename(ExportFilename::forCurrentScope(__('Stocks')))
                        ->askForWriterType(Excel::XLSX),
                ]),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'Positive';
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make(),
            'Positive' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                return $query->where('stock', '>', '0');
            }),
            'Zero' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                return $query->where('stock', '=', '0');
            }),
            // 'Negative' => Tab::make()->modifyQueryUsing(function (Builder $query) {
            //     return $query->where('stock', '<', '0');
            // }),
        ];
    }
}
