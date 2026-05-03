<?php

namespace App\Filament\Resources\MovementTypeResource\Pages;

use App\Filament\Resources\MovementTypeResource;
use App\Traits\ViewHeaderAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMovementType extends ViewRecord
{
    use ViewHeaderAction;
    protected static string $resource = MovementTypeResource::class;
}
