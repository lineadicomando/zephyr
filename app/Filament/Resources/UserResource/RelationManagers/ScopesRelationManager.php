<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Scope;
use App\Models\User;
use Closure;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * Limit listed and attachable scopes to the ones the current user belongs to.
     */
    protected function scopeToManageableScopes(Builder $query): Builder
    {
        return $query->visibleTo(auth()->user());
    }

    protected function canManageScopeMembership(?Scope $scope): bool
    {
        return $scope !== null && (bool) auth()->user()?->can('manageMembership', $scope);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $this->scopeToManageableScopes($query))
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
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $this->scopeToManageableScopes($query))
                    ->recordSelect(fn (Select $select): Select => $select
                        ->searchable()
                        ->preload()
                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (! $this->canManageScopeMembership(Scope::query()->find($value))) {
                                $fail(__('You cannot assign this scope.'));
                            }
                        })),
            ])
            ->actions([
                DetachAction::make()
                    ->authorize(fn (Scope $record): bool => $this->canManageScopeMembership($record))
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
