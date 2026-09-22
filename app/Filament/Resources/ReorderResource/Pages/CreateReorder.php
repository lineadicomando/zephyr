<?php

namespace App\Filament\Resources\ReorderResource\Pages;

use App\Filament\Resources\ReorderResource;
use App\Traits\CancelToCloseAction;
use App\Traits\EditFirstRedirectUrlOverride;
use Filament\Resources\Pages\CreateRecord;

class CreateReorder extends CreateRecord
{
    use CancelToCloseAction, EditFirstRedirectUrlOverride;

    protected static string $resource = ReorderResource::class;
}
