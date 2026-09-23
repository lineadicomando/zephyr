<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\BarcodeScannerInput;
use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\InventoryResource\RelationManagers\StocksRelationManager;
use App\Filament\Resources\InventoryResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\StockResource\Pages\ListStocks;
use App\Filament\Tables\Filters\InventoryFilters;
use App\Models\Inventory;
use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    // protected static string|\UnitEnum|null $navigationGroup = "Inventory";

    // public static function getNavigationGroup(): ?string
    // {
    //     return __(static::$navigationGroup);
    // }

    protected static ?int $navigationSort = 1;

    // public static function getNavigationGroup(): ?string
    // {
    //     return __(static::$navigationGroup);
    // }

    protected static ?string $recordTitleAttribute = 'summary';

    public static function getModelLabel(): string
    {
        return __('Inventory');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Inventory');
    }

    public static function getFormDefinition()
    {
        return [
            TextInput::make('inventory_number')
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('scope_id', filament()->getTenant()?->id),
                )
                ->helperText(__('Leave blank for automatic assignment.'))
                ->translateLabel(),
            BarcodeScannerInput::make('serial_number')
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('scope_id', filament()->getTenant()?->id),
                )
                ->translateLabel(),
            Select::make('product_id')
                ->required()
                ->translateLabel()
                ->searchable()
                ->preload()
                ->relationship('product', 'name')
                ->live()
                ->createOptionForm(auth()->user()?->can('create', Product::class) ? ProductResource::getFormDefinition() : null)
                ->editOptionForm(auth()->user()?->can('update', Product::class) ? ProductResource::getFormDefinition() : null),
            TextInput::make('description')
                ->translateLabel(),
            TextInput::make('mac_address')
                ->macAddress()
                ->translateLabel(),
            TextInput::make('url')
                ->translateLabel()
                ->url(),
            Textarea::make('note')
                ->translateLabel(),
            TextInput::make('summary')
                ->translateLabel()
                ->disabled(),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('inventory_number')
                    ->label('Inventory')
                    ->translateLabel()
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->sortable(),
                TextColumn::make('product.product_group.name')
                    ->label('Group')
                    ->translateLabel()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.product_type.name')
                    ->label('Type')
                    ->translateLabel()
                    ->searchable(isIndividual: true)
                    ->sortable(),
                TextColumn::make('product.product_brand.name')
                    ->label('Brand')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.product_model.name')
                    ->label('Model')
                    ->translateLabel()
                    // ->searchable()
                    // ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.name')
                    ->translateLabel()
                    ->searchable(isIndividual: true)
                    ->sortable(),
                TextColumn::make('description')
                    ->translateLabel()
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->translateLabel()
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('mac_address')
                    ->translateLabel()
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('note')
                    ->translateLabel()
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('non_zero_stocks.path')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('Stocks')
                    ->translateLabel()
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),
                TextColumn::make('url')
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
            ->persistSortInSession()
            ->filtersFormColumns(2)
            ->filters([
                TernaryFilter::make('non_zero_stocks')
                    ->columnSpanFull()
                    ->translateLabel()
                    ->label('Availability')
                    ->placeholder(__('All'))
                    ->trueLabel(__('Available'))
                    ->falseLabel(__('Not available'))
                    ->queries(
                        true: fn (Builder $query) => $query->has('non_zero_stocks'),
                        false: fn (Builder $query) => $query->doesntHave('non_zero_stocks'),
                        blank: fn (Builder $query) => $query, // In this example, we do not want to filter the query when it is blank.
                    ),
                ...InventoryFilters::make(stockRelation: 'non_zero_stocks', productRelation: 'product'),
            ])
            ->persistFiltersInSession()
            ->recordUrl(function ($record) {
                if (auth()->user()->can('update', Inventory::class)) {
                    return Pages\EditInventory::getUrl([$record->id]);
                }

                return Pages\ViewInventory::getUrl([$record->id]);
            })
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            StocksRelationManager::class,
            MovementsRelationManager::class,
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'stocks' => ListStocks::route('/stocks'),
            'products' => ListProducts::route('/products'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
            'view' => Pages\ViewInventory::route('/{record}/view'),
        ];
    }
}
