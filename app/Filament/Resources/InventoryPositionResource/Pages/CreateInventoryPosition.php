<?php

namespace App\Filament\Resources\InventoryPositionResource\Pages;

use App\Filament\Resources\InventoryPositionResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryPosition extends CreateRecord
{
    use CancelToCloseAction;
    protected static string $resource = InventoryPositionResource::class;
}
