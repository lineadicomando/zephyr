<?php

namespace App\Filament\Resources\ScopeResource\Pages;

use App\Filament\Resources\ScopeResource;
use App\Traits\CancelToCloseAction;
use Filament\Resources\Pages\EditRecord;

class EditScope extends EditRecord
{
    use CancelToCloseAction;

    protected static string $resource = ScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ScopeResource::requestDeletionAction(),
        ];
    }
}
