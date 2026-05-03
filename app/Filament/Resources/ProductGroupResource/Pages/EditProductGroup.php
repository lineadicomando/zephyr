<?php

namespace App\Filament\Resources\ProductGroupResource\Pages;

use App\Filament\Resources\ProductGroupResource;
use App\Models\ProductGroup;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductGroup extends EditRecord
{

    use CancelToCloseAction;
    protected static string $resource = ProductGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            Actions\DeleteAction::make()->hidden(fn (ProductGroup $record) => $record->hasRelated()),
        ];
    }
}
