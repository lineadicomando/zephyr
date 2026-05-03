<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLocationResource\Pages;
use App\Filament\Resources\InventoryLocationResource\RelationManagers;
use App\Filament\Resources\InventoryLocationResource\RelationManagers\InventoryPositionsRelationManager;
use App\Contracts\ScopeContext;
use App\Models\InventoryLocation;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class InventoryLocationResource extends Resource
{
    protected static ?string $model = InventoryLocation::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-europe-africa';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = "Inventory";

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        return (__('Inventory Position'));
    }

    public static function getPluralModelLabel(): string
    {
        return (__('Inventory positions'));
    }


    public static function getFormDefinition()
    {
        return [
            TextInput::make('name')
                ->translateLabel()
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('scope_id', app(ScopeContext::class)->activeScopeId()),
                ),
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
                TextColumn::make('name')
                    ->label('Location')
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
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can('update', InventoryLocation::class)) {
                    return Pages\EditInventoryLocation::getUrl([$record->id]);
                }
                return Pages\ViewInventoryLocation::getUrl([$record->id]);
            })
            ->actions([
                \Filament\Actions\ViewAction::make(),
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
            InventoryPositionsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryLocations::route('/'),
            'create' => Pages\CreateInventoryLocation::route('/create'),
            'edit' => Pages\EditInventoryLocation::route('/{record}/edit'),
            'view' => Pages\ViewInventoryLocation::route('/{record}/view'),
        ];
    }
}
