<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use App\Models\Tag;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTag extends EditRecord
{
    use CancelToCloseAction;

    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->hidden(fn (Tag $record) => $record->hasRelated()),
        ];
    }
}
