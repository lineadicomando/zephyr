<?php

namespace App\Filament\Resources\MovementResource\Pages;

use App\Filament\Resources\MovementResource;
use App\Models\Movement;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMovement extends EditRecord
{
    use CancelToCloseAction;
    protected static string $resource = MovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn (Movement $record) => $record->hasRelated())
                ->after(function (Action $action, Movement $movement) {
                    \Filament\Notifications\Notification::make()
                        ->title(__('Error'))
                        ->danger()
                        ->body($movement?->failureState)
                        ->send();
                }),
        ];
    }
}
