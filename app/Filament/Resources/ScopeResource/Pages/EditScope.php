<?php

namespace App\Filament\Resources\ScopeResource\Pages;

use App\Filament\Resources\ScopeResource;
use App\Models\Scope;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditScope extends EditRecord
{
    use CancelToCloseAction;

    protected static string $resource = ScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('requestDeletion')
                ->label(__('Request deletion'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Scope $record): void {
                    try {
                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title(__('Deletion requested'))
                            ->body(__('Scope deletion has been scheduled.'))
                            ->send();
                    } catch (\Throwable $throwable) {
                        Notification::make()
                            ->danger()
                            ->title(__('Deletion request failed'))
                            ->body(__($throwable->getMessage()))
                            ->send();
                    }
                }),
        ];
    }
}
