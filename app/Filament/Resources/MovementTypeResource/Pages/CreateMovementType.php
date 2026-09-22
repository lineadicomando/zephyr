<?php

namespace App\Filament\Resources\MovementTypeResource\Pages;

use App\Filament\Resources\MovementTypeResource;
use App\Traits\CancelToCloseAction;
use Filament\Resources\Pages\CreateRecord;

class CreateMovementType extends CreateRecord
{
    use CancelToCloseAction;

    protected static string $resource = MovementTypeResource::class;
}
