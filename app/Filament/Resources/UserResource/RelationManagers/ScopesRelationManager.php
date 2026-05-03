<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ScopesRelationManager extends RelationManager
{
    protected static string $relationship = 'scopes';

    protected function shouldBlockLastScopeDetach(): bool
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof User) {
            return false;
        }

        return $owner->scopes()->count() <= 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelect(fn (Select $select): Select => $select->searchable()->preload()),
            ])
            ->actions([
                DetachAction::make()
                    ->hidden(fn (): bool => $this->shouldBlockLastScopeDetach())
                    ->before(function (DetachAction $action): void {
                        if (! $this->shouldBlockLastScopeDetach()) {
                            return;
                        }

                        Notification::make()
                            ->warning()
                            ->title(__('Cannot detach last scope'))
                            ->body(__('At least one scope must remain assigned when scope enforcement is enabled.'))
                            ->send();

                        $action->halt();
                    }),
            ])
            ->bulkActions([]);
    }
}
