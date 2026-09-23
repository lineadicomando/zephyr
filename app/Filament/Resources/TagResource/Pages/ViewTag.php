<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use App\Traits\ViewHeaderAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTag extends ViewRecord
{
    use ViewHeaderAction;

    protected static string $resource = TagResource::class;
}
