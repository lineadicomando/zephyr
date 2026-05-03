<?php

namespace App\Filament\Resources\ProductBrandResource\Pages;

use App\Filament\Resources\ProductBrandResource;
use App\Models\ProductBrand;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductBrand extends EditRecord
{
    use CancelToCloseAction;
    protected static string $resource = ProductBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            Actions\DeleteAction::make()->hidden(fn (ProductBrand $record) => $record->hasRelated()),
        ];
    }
}
