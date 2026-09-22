<?php

namespace App\Filament\Resources\InventoryLocationResource\Pages;

use App\Filament\Resources\InventoryLocationResource;
use App\Traits\ViewHeaderAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryLocation extends ViewRecord
{
    use ViewHeaderAction;

    protected static string $resource = InventoryLocationResource::class;
}
