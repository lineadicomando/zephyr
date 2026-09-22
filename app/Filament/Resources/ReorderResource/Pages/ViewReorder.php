<?php

namespace App\Filament\Resources\ReorderResource\Pages;

use App\Filament\Resources\ReorderResource;
use App\Traits\ViewHeaderAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReorder extends ViewRecord
{
    use ViewHeaderAction;

    protected static string $resource = ReorderResource::class;
}
