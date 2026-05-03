<?php

namespace App\Filament\Resources\InventoryLocationResource\RelationManagers;

use App\Models\InventoryPosition;
use Filament\Forms\Components\Hidden;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class InventoryPositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventory_positions';
    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Positions');
    }
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Hidden::make('scope_id')
                    ->default(fn (): ?int => $this->getOwnerRecord()->scope_id),
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
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()->hidden(fn (InventoryPosition $record) => $record->hasRelated()),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
