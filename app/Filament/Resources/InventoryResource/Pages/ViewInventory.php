<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Traits\ViewHeaderAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInventory extends ViewRecord
{
    use ViewHeaderAction;
    protected static string $resource = InventoryResource::class;
}
