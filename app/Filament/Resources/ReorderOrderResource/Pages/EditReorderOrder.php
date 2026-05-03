<?php

namespace App\Filament\Resources\ReorderOrderResource\Pages;

use App\Filament\Resources\ReorderOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReorderOrder extends EditRecord
{
    protected static string $resource = ReorderOrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
