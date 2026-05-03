<?php

namespace App\Filament\Resources\ScopeResource\Pages;

use App\Filament\Resources\ScopeResource;
use App\Traits\CancelToCloseAction;
use Filament\Resources\Pages\CreateRecord;

class CreateScope extends CreateRecord
{
    use CancelToCloseAction;

    protected static string $resource = ScopeResource::class;
}
