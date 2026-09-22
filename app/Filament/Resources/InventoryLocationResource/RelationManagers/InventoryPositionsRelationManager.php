<?php

namespace App\Filament\Resources\InventoryLocationResource\RelationManagers;

use App\Models\InventoryPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class InventoryPositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventory_positions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Positions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->rule(function (?InventoryPosition $record) {
                        return Rule::unique('inventory_positions', 'name')
                            ->where('scope_id', $this->getOwnerRecord()->scope_id)
                            ->where('inventory_location_id', $this->getOwnerRecord()->id)
                            ->ignore($record?->id);
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('default', false))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->translateLabel(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (InventoryPosition $record) => $record->hasRelated()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
