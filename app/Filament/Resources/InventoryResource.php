<?php

namespace App\Filament\Resources;

use App\Contracts\ScopeContext;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use App\Models\Inventory;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\InventoryPosition;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationGroup;
use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryLocationResource;
use App\Filament\Resources\InventoryPositionResource;
use App\Filament\Resources\StockResource\Pages\ListStocks;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\InventoryResource\RelationManagers\TasksRelationManager;

use App\Filament\Resources\InventoryResource\RelationManagers\StocksRelationManager;
use App\Filament\Resources\InventoryResource\RelationManagers\MovementsRelationManager;


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
        return (__('Inventory'));
    }

    public static function getPluralModelLabel(): string
    {
        return (__('Inventory'));
    }

    public static function InventoryLocationAfterStateUpdated(string $positionField, Set &$set, ?string $state)
    {
        $set($positionField, '');
    }

    public static function InventoryPositionHelperText(Get &$get, ?bool &$activeSelection)
    {
        if ($activeSelection) {
            return __('Active selection.');
        }
        return '';
    }

    public static function InventoryPositionModifyQueryUsing(string $locationField, string $positionField, Builder &$query, Set &$set, Get &$get, ?bool &$activeSelection)
    {
        $inventoryLocationId = $get($locationField);
        $inventoryPositionId = $get($positionField);
        if (!empty($inventoryLocationId) && empty($inventoryPositionId)) {
            $activeSelection = true;
            return $query->where('inventory_location_id', $inventoryLocationId);
        }
        if (!empty($inventoryPositionId)) {
            $inventoryPosition = InventoryPosition::find($inventoryPositionId);
            if ($inventoryPosition) {
                $set($locationField, $inventoryPosition->inventory_location_id);
            }
        }
        $activeSelection = false;
        return $query;
    }

    public static function getFormDefinition()
    {
        $userIsAdmin = auth()->user()?->isAdmin();
        return [
            Hidden::make('scope_id')
                ->default(fn (): ?int => app(ScopeContext::class)->activeScopeId())
                ->dehydrated(),
            TextInput::make('inventory_number')
                ->unique(ignoreRecord: true)
                ->helperText(__('Leave blank for automatic assignment.'))
                ->translateLabel(),
            TextInput::make('serial_number')
                ->unique(ignoreRecord: true)
                ->translateLabel(),
            Select::make('product_id')
                ->required()
                ->translateLabel()
                ->searchable()
                ->preload()
                ->relationship('product', 'name')
                ->live()
                ->createOptionForm($userIsAdmin ? ProductResource::getFormDefinition() : null)
                ->editOptionForm($userIsAdmin ? ProductResource::getFormDefinition() : null),
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
                $locationFilter = SelectFilter::make('location')
                    ->label('Location')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('non_zero_stocks.inventory_location', 'name'),
                SelectFilter::make('position')
                    ->label('Position')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('non_zero_stocks.inventory_position', 'path', function (Builder $query) use (&$locationFilter) {
                        $locationState = $locationFilter->getState();
                        if (!empty($locationState['value'])) {
                            return $query->where('inventory_location_id', $locationState['value']);
                        }
                        return $query;
                    }),
                SelectFilter::make('product_group_id')
                    ->label('Group')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product.product_group', 'name'),
                SelectFilter::make('product_type_id')
                    ->label('Type')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product.product_type', 'name'),
                $brandFilter = SelectFilter::make('product_brand_id')
                    ->label('Brand')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product.product_brand', 'name'),
                SelectFilter::make('product_model_id')
                    ->label('Model')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product.product_model', 'name', function (Builder $query) use (&$brandFilter) {
                        $brandeState = $brandFilter->getState();
                        if (!empty($brandeState['value'])) {
                            return $query->where('product_brand_id', $brandeState['value']);
                        }
                        return $query;
                    })
            ])
            ->persistFiltersInSession()
            ->recordUrl(function ($record) use ($table) {
                if (auth()->user()->can('update', Inventory::class)) {
                    return Pages\EditInventory::getUrl([$record->id]);
                }
                return Pages\ViewInventory::getUrl([$record->id]);
            })
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
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
