<?php

namespace App\Filament\Resources\ScopeResource\Pages;

use App\Filament\Resources\ScopeResource;
use App\Traits\ViewHeaderAction;
use Filament\Resources\Pages\ViewRecord;

class ViewScope extends ViewRecord
{
    use ViewHeaderAction;

    protected static string $resource = ScopeResource::class;
}
