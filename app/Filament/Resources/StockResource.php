<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Filament\Resources\StockResource\RelationManagers;
use App\Models\ProductType;
use App\Models\Stock;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
        return (__('Stock'));
    }

    public static function getPluralModelLabel(): string
    {
        return (__('Stocks'));
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
                $inventoryLocation = SelectFilter::make('inventory_location')
                    ->label('Location')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('inventory_location', 'name'),
                SelectFilter::make('inventory_position')
                    ->label('Position')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('inventory_position', 'path', function (Builder $query) use ($inventoryLocation) {
                        $locationState = $inventoryLocation->getState();
                        if (!empty($locationState['value'])) {
                            return $query->where('inventory_location_id', $locationState['value']);
                        }
                        return $query;
                    }),
                SelectFilter::make('product_group')
                    ->label('Group')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_group', 'name'),
                SelectFilter::make('product_type')
                    ->label('Type')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_type', 'name'),
                $productBrand = SelectFilter::make('product_brand')
                    ->label('Brand')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_brand', 'name'),
                SelectFilter::make('product_model')
                    ->label('Model')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_model', 'name', function (Builder $query) use ($productBrand) {
                        $productBrandState = $productBrand->getState();
                        if (!empty($productBrandState['value'])) {
                            return $query->where('product_brand_id', $productBrandState['value']);
                        }
                        return $query;
                    }),
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
