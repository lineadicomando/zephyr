<?php

namespace App\Filament\Resources\MovementItemResource\Pages;

use App\Filament\Resources\MovementItemResource;
use App\Filament\Resources\MovementResource;
use App\Support\Export\ExportFilename;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListMovementItems extends ListRecords
{
    protected static string $resource = MovementItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('movements')
                ->translateLabel()
                ->icon('heroicon-o-arrows-right-left')
                ->label('Movements')
                ->url(fn (): string => MovementResource::getUrl()),
            ExportAction::make('table')
                ->translateLabel()
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->askForFilename(ExportFilename::forCurrentScope(__('Movements')))
                        ->askForWriterType(Excel::XLSX),
                ]),
        ];
    }
}
