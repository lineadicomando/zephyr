<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Traits\CancelToCloseAction;
use App\Traits\EditFirstRedirectUrlOverride;
use Filament\Resources\Pages\CreateRecord;

class CreateInventory extends CreateRecord
{
    use CancelToCloseAction, EditFirstRedirectUrlOverride;

    protected static string $resource = InventoryResource::class;
}
