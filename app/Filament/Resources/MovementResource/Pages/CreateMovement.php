<?php

namespace App\Filament\Resources\MovementResource\Pages;

use App\Filament\Resources\MovementResource;
use App\Traits\CancelToCloseAction;
use App\Traits\EditFirstRedirectUrlOverride;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMovement extends CreateRecord
{
    use CancelToCloseAction, EditFirstRedirectUrlOverride;
    protected static string $resource = MovementResource::class;
}
