<?php

namespace App\Filament\Resources;

use App\Contracts\ScopeContext;
use App\Filament\Resources\InventoryPositionResource\Pages;
use App\Filament\Resources\InventoryPositionResource\RelationManagers;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class InventoryPositionResource extends Resource
{
    protected static ?string $model = InventoryPosition::class;
    //
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $recordTitleAttribute = 'name';
    protected static string|\UnitEnum|null $navigationGroup = "Inventory";

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        return (__('Inventory position'));
    }

    public static function getPluralModelLabel(): string
    {
        return (__('Inventory positions'));
    }

    public static function getFormDefinition()
    {
        return [
            Select::make('inventory_location_id')
                ->translateLabel()
                ->relationship('inventory_location', 'name')
                ->createOptionForm(InventoryLocationResource::getFormDefinition())
                ->editOptionForm(InventoryLocationResource::getFormDefinition()),
            TextInput::make('name')
                ->required()
                ->translateLabel()
                ->rule(function (Get $get, ?InventoryPosition $record) {
                    return Rule::unique('inventory_positions', 'name')
                        ->where('scope_id', app(ScopeContext::class)->activeScopeId())
                        ->where('inventory_location_id', $get('inventory_location_id'))
                        ->ignore($record?->id);
                }),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(self::getFormDefinition());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('inventory_location.name')
                    ->sortable()
                    ->translateLabel()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(isGlobal: true)
                    ->sortable()
                    ->translateLabel(),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistSearchInSession()
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryPositions::route('/'),
            'create' => Pages\CreateInventoryPosition::route('/create'),
            'edit' => Pages\EditInventoryPosition::route('/{record}/edit'),
        ];
    }
}
