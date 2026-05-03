<?php

namespace App\Traits;

use Filament\Actions\Action;
use Filament\Actions\EditAction;

trait ViewHeaderAction
{
    protected function getHeaderActions(): array
    {
        $rUrl = static::getResource()::getUrl();
        $previousUrl = url()->previous();
        if ($previousUrl == $rUrl || preg_match('/.*\?page=[0-9]+.*/', $previousUrl)) {
            session()->put('previousUrl', $previousUrl);
        }
        $previousUrl = session()->get('previousUrl', $rUrl);
        return [
            EditAction::make(),
            Action::make('cancel')
                ->label(__('Close'))
                ->url($previousUrl ?? $rUrl)
                ->color('gray')
        ];
    }
}
