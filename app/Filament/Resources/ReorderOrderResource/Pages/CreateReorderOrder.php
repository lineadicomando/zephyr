<?php

namespace App\Filament\Resources\ReorderOrderResource\Pages;

use App\Filament\Resources\ReorderOrderResource;
use App\Models\ReorderOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateReorderOrder extends CreateRecord
{
    protected static string $resource = ReorderOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ReorderOrder::STATUS_DRAFT;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
