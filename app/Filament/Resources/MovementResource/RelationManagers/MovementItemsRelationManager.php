<?php

namespace App\Filament\Resources\MovementResource\RelationManagers;

use App\Filament\Resources\InventoryResource;
use App\Models\Inventory;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class MovementItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'movement_items';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Movement Items');
    }

    public function form(Schema $schema): Schema
    {
        // \Illuminate\Support\Facades\DB::listen(function ($query) {
        //     Log::debug($query->sql);
        //     Log::debug($query->bindings);
        // });
        $inventoryTable = app(Inventory::class)->getTable();
        return $schema
            ->schema([
                Select::make('inventory_id')
                    ->translateLabel()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (!is_null($this->ownerRecord->from_inventory_position_id)) {
                            $availability = Stock::findAvailability(
                                inventoryId: $state,
                                positionId: $this->ownerRecord->from_inventory_position_id
                            );
                            $set('availability', $availability);
                        }
                    })
                    ->relationship('inventory', "summary", modifyQueryUsing: function (Builder $query) {
                        if (!is_null($this->ownerRecord->from_inventory_position_id)) {
                            $query->join('stocks', 'inventories.id', '=', 'stocks.inventory_id');
                            $query->where('stocks.stock', '>', '0');
                            $query->where('stocks.inventory_position_id', $this->ownerRecord->from_inventory_position_id);
                        }
                        return $query;
                    })
                    // ->createOptionForm(InventoryResource::getFormDefinition())
                    // ->editOptionForm(InventoryResource::getFormDefinition())
                    ->columnSpanFull(),
                TextInput::make('availability')
                    ->translateLabel()
                    ->disabled()
                    ->numeric(),
                TextInput::make('stock')
                    ->label('Qty')
                    ->default(1)
                    ->translateLabel()
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('inventory_summary')
            ->columns([
                TextColumn::make('inventory_summary')
                    ->searchable()
                    ->translateLabel(),
                TextColumn::make('outcoming_stock.stock')
                    ->label('Residual availability')
                    ->translateLabel(),
                // TextInputColumn::make('stock'),
                TextColumn::make('stock')
                    ->translateLabel()
                    ->label('Qty'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\DeleteAction::make()
                    ->hidden(fn (\App\Models\MovementItem $movementItem) => !$movementItem->isLast())
                    ->label(''),
                \Filament\Actions\EditAction::make()
                    ->hidden(fn (\App\Models\MovementItem $movementItem) => !$movementItem->isLast())
                    ->label('')
                    ->form([
                        TextInput::make('stock')
                            ->label('Qty')
                            ->default(1)
                            ->translateLabel()
                            ->required()
                            ->numeric(),
                    ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
