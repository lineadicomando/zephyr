<?php

namespace App\Filament\Resources\MovementItemResource\Pages;

use Filament\Actions;
use Maatwebsite\Excel\Excel;
use Filament\Resources\Pages\ListRecords;
use App\Support\Export\ExportFilename;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\MovementResource;
use App\Filament\Resources\MovementItemResource;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

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
                        ->askForWriterType(Excel::XLSX)
                ])
        ];
    }
}
