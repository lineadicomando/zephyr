<?php

namespace App\Filament\Resources\ProductModelResource\Pages;

use App\Filament\Resources\ProductModelResource;
use App\Models\ProductModel;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductModel extends EditRecord
{
    use CancelToCloseAction;
    protected static string $resource = ProductModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->hidden(fn (ProductModel $record) => $record->hasRelated()),
        ];
    }
}
