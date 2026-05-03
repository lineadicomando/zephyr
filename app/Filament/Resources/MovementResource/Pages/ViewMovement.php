<?php

namespace App\Filament\Resources\MovementResource\Pages;

use App\Filament\Resources\MovementResource;
use App\Traits\ViewHeaderAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMovement extends ViewRecord
{
    use ViewHeaderAction;
    protected static string $resource = MovementResource::class;
}
