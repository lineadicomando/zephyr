<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Filament\Tables\Filters\InventoryFilters;
use App\Models\Stock;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static bool $shouldRegisterNavigation = false;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    // protected static ?int $navigationSort = 5;

    // protected static string|\UnitEnum|null $navigationGroup = "Inventory";

    // public static function getNavigationGroup(): ?string
    // {
    //     return __(static::$navigationGroup);
    // }
    public static function getModelLabel(): string
    {
        return __('Stock');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stocks');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#'),
                TextColumn::make('inventory.inventory_number')
                    ->translateLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product_group.name')
                    ->label('Group')
                    ->translateLabel()
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product_type.name')
                    ->label('Type')
                    ->translateLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product_brand.name')
                    ->label('Brand')
                    ->translateLabel()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('product_model.name')
                    ->label('Model')
                    ->translateLabel()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('product.name')
                    ->translateLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('inventory.description')
                    ->label('Description')
                    ->translateLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('inventory.serial_number')
                    ->label('Serial Number')
                    ->translateLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('inventory_position.path')
                    ->label('Position')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('stock')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('inventory.mac_address')
                    ->label('MAC Address')
                    ->translateLabel()
                    // ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('inventory.url')
                    ->label('URL')
                    ->translateLabel()
                    // ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('inventory.note')
                    ->label('Note')
                    ->translateLabel()
                    // ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->translateLabel()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->filtersFormColumns(2)
            ->filters([
                ...InventoryFilters::make(),
            ])
            ->persistFiltersInSession()
            ->actions([
                // \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // \Filament\Actions\BulkActionGroup::make([
                //     \Filament\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListStocks::route('/'),
            'create' => Pages\CreateStock::route('/create'),
            // 'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
}
