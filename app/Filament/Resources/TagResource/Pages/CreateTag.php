<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use App\Traits\CancelToCloseAction;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    use CancelToCloseAction;

    protected static string $resource = TagResource::class;
}
