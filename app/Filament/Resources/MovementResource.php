<?php

namespace App\Filament\Resources;

use App\Contracts\ScopeContext;
use App\Filament\Resources\InventoryLocationResource;
use App\Filament\Resources\InventoryPositionResource;
use App\Filament\Resources\MovementItemResource\Pages\ListMovementItems;
use App\Filament\Resources\MovementResource\Pages;
use App\Filament\Resources\MovementResource\RelationManagers;
use App\Filament\Resources\MovementResource\RelationManagers\MovementItemsRelationManager;
use App\Models\Movement;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovementResource extends Resource
{
    protected static ?string $model = Movement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    // protected static string|\UnitEnum|null $navigationGroup = "Movements";

    protected static ?int $navigationSort = 2;

    // public static function getNavigationGroup(): ?string
    // {
    //     return __(static::$navigationGroup);
    // }

    protected static ?string $recordTitleAttribute = 'description';

    public static function getModelLabel(): string
    {
        return (__('Movement'));
    }

    public static function getPluralModelLabel(): string
    {
        return (__('Movements'));
    }

    public static function form(Schema $schema): Schema
    {
        $userIsAdmin = auth()->user()?->isAdmin();
        return $schema
            ->schema([
                Hidden::make('scope_id')
                    ->default(fn (): ?int => app(ScopeContext::class)->activeScopeId())
                    ->dehydrated(),
                DateTimePicker::make('date')
                    ->required()
                    ->default(date('Y-m-d h:i'))
                    ->seconds(false)
                    ->translateLabel(),
                Select::make('movement_type_id')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->translateLabel()
                    ->relationship('movement_type', 'name'),
                Select::make('from_inventory_position_id')
                    ->label('Origin position')
                    ->disabled(fn ($record) => !is_null($record))
                    ->requiredWithout('to_inventory_position_id')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship(name: 'from_inventory_position', titleAttribute: 'path')
                    ->createOptionForm($userIsAdmin ? InventoryPositionResource::getFormDefinition() : null)
                    ->editOptionForm($userIsAdmin ? InventoryPositionResource::getFormDefinition() : null),
                Select::make('to_inventory_position_id')
                    ->requiredWithout('from_inventory_position_id')
                    ->label('Destination position')
                    ->disabled(fn ($record) => !is_null($record))
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship(name: 'to_inventory_position', titleAttribute: 'path')
                    ->createOptionForm($userIsAdmin ? InventoryPositionResource::getFormDefinition() : null)
                    ->editOptionForm($userIsAdmin ? InventoryPositionResource::getFormDefinition() : null),
                TextInput::make('description')
                    ->translateLabel()
                    ->maxLength(255)
                    ->required(),
                Textarea::make('note')->translateLabel(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#'),
                TextColumn::make('date')
                    ->translateLabel()
                    ->date('j M Y')
                    ->sortable(),
                TextColumn::make('movement_type.name')
                    // ->html()
                    // ->formatStateUsing(function (string $state, Movement $movement): string {
                    //     $out = [];
                    //     if ($movement->from_inventory_position?->path) {
                    //         $out[] = $movement->from_inventory_position?->path;
                    //     }
                    //     if ($movement->to_inventory_position?->path) {
                    //         $out[] = $movement->to_inventory_position?->path;
                    //     }
                    //     return  __($state . "<br />\n" . implode(' > ', $out));
                    // })
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('from_inventory_position.path')
                    ->label('Origin')
                    ->translateLabel()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('to_inventory_position.path')
                    ->label('Destination')
                    ->wrap()
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(isGlobal: true)
                    ->wrap()
                    ->limit(50)
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('movement_items.inventory.product.name')
                    ->label('Products')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->wrap()
                    ->translateLabel(),
                TextColumn::make('created_at')
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->filters([
                SelectFilter::make('movement_type')
                    ->translateLabel()
                    ->preload()
                    ->searchable()
                    ->relationship('movement_type', 'name'),
                SelectFilter::make('from_inventory_position')
                    ->label('Origin position')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('from_inventory_position', 'path'),
                SelectFilter::make('to_inventory_position')
                    ->label('Destination position')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('to_inventory_position', 'path'),
                SelectFilter::make('movement_items')
                    ->label('Products')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('movement_items', 'inventory_summary'),

            ])
            ->persistFiltersInSession()
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can('update', Movement::class)) {
                    return Pages\EditMovement::getUrl([$record->id]);
                }
                return Pages\ViewMovement::getUrl([$record->id]);
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
            MovementItemsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovements::route('/'),
            'movement-items' => ListMovementItems::route('/movement-items'),
            'create' => Pages\CreateMovement::route('/create'),
            'edit' => Pages\EditMovement::route('/{record}/edit'),
            'view' => Pages\ViewMovement::route('/{record}/view'),
        ];
    }
}
