<?php

namespace App\Filament\Resources\InventoryLocationResource\Pages;

use App\Filament\Resources\InventoryLocationResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryLocation extends CreateRecord
{
    use CancelToCloseAction;

    protected static string $resource = InventoryLocationResource::class;
}
