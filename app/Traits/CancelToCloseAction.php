<?php

namespace App\Traits;

use Filament\Actions\Action;

trait CancelToCloseAction
{
    protected function getCancelFormAction(): Action
    {
        $backUrl = $this->previousUrl ?? static::getResource()::getUrl();
        if (preg_match('/.+\/create.*/', $backUrl)) {
            $backUrl = static::getResource()::getUrl();
        }
        return Action::make('cancel')
            ->label(__('Close'))
            ->url($backUrl)
            ->color('gray');
    }
}
