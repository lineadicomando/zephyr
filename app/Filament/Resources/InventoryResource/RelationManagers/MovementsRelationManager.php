<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\Stock;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Livewire\Notifications;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movement_items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Movements');
    }

    public function form(Schema $schema): Schema
    {
        $ownerRecord = $this->getOwnerRecord();
        return $schema
            ->schema([
                \Filament\Forms\Components\Hidden::make('inventory_id')
                    ->default($ownerRecord->id),
                \Filament\Forms\Components\DateTimePicker::make('date')
                    ->required()
                    ->disabled(fn ($record) => !is_null($record))
                    ->default(date('Y-m-d h:i'))
                    ->seconds(false)
                    ->translateLabel(),
                \Filament\Forms\Components\Select::make('movement_type_id')
                    ->label('Movement type')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->translateLabel()
                    ->options(MovementType::all()->sortBy('name')->pluck('name', 'id')),
                \Filament\Forms\Components\Select::make('from_inventory_position_id')
                    ->label('Origin position')
                    ->disabled(fn ($record) => !is_null($record))
                    ->translateLabel()
                    ->searchable()
                    ->live()
                    ->preload()
                    ->options(function (\App\Models\Stock $stock, Get $get) use ($ownerRecord) {
                        $query = $stock
                            ->select('path', 'inventory_position_id as id')
                            ->where('stock', '>', 0)
                            ->where('inventory_id', $ownerRecord->id);
                        return $query->get()->sortBy('path')->pluck('path', 'id');
                    }),
                \Filament\Forms\Components\Select::make('to_inventory_position_id')
                    ->label('Destination position')
                    ->disabled(fn ($record) => !is_null($record))
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->options(function (InventoryPosition $inventoryPosition, Get $get) {
                        $query = $inventoryPosition->select('path', 'id')->where('id', '<>', $get('from_inventory_position_id'));
                        return $query->get()->sortBy('path')->pluck('path', 'id');
                    }),
                \Filament\Forms\Components\TextInput::make('stock')
                    ->label('Qty')
                    ->disabled(fn ($record) => !is_null($record))
                    ->default(1)
                    ->translateLabel()
                    ->required()
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('description')
                    ->translateLabel()
                    ->maxLength(255)
                    ->required(),
                \Filament\Forms\Components\Textarea::make('note')->translateLabel(),
            ]);
    }

    public static function ActionsBeforeFormFilled(array $data, string $model, MovementItem $movementItem)
    {
        $movement = Movement::find($movementItem->movement_id);
        $movementItem->description = $movement->description;
        $movementItem->date = $movement->date;
        $movementItem->movement_type_id = $movement->movement_type_id;
        $movementItem->from_inventory_position_id = $movement->from_inventory_position_id;
        $movementItem->to_inventory_position_id = $movement->to_inventory_position_id;
        $movementItem->note = $movement->note;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('movement.description')
            ->columns([
                TextColumn::make('movement.id')
                    ->label('#')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('movement.date')
                    ->translateLabel()
                    ->date('j M Y')
                    ->sortable(),
                TextColumn::make('movement.movement_type.name')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('movement.from_inventory_position.path')
                    ->label('Origin')
                    ->translateLabel()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('movement.to_inventory_position.path')
                    ->label('Destination')
                    ->wrap()
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('movement.description')->searchable()
                    ->label('Description')
                    ->wrap()
                    ->limit(50)
                    ->translateLabel()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('movement_type')
                    ->translateLabel()
                    ->preload()
                    ->searchable()
                    ->relationship('movement.movement_type', 'name')
                    ->columnSpanFull(),
                SelectFilter::make('from_inventory_position')
                    ->label('Origin position')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('movement.from_inventory_position', 'path'),
                SelectFilter::make('to_inventory_position')
                    ->label('Destination position')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('movement.to_inventory_position', 'path')
            ])->filtersFormColumns(2)
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->using(function (array $data, $action,  string $model): Model|bool {

                        if (empty($data['inventory_id'])) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->icon('heroicon-o-exclamation-triangle')
                                ->title(__('Error'))
                                ->body(__('Unknown error, contact support'))
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('Close')
                                        ->button()
                                        ->close()
                                ])
                                ->send();
                            $action->halt();
                            return false;
                        }

                        if (!empty($data['from_inventory_position_id'])) {
                            $stock = Stock::findAvailability(
                                inventoryId: $data['inventory_id'],
                                positionId: $data['from_inventory_position_id'],
                            );
                            $newStock = $stock - $data['stock'];
                            if ($newStock < 0) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title(__('Warning'))
                                    ->body(__('Insufficient availability, impossible to proceed'))
                                    ->persistent()
                                    ->send();
                                $action->halt();
                                return false;
                            }
                        }

                        $movement = \App\Models\Movement::create($data);
                        if (!$movement->id) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->icon('heroicon-o-exclamation-triangle')
                                ->title(__('Error'))
                                ->body(__('Unknown error, contact support'))
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('Close')
                                        ->button()
                                        ->close()
                                ])
                                ->send();
                            $action->halt();
                            return false;
                        }
                        $data['movement_id'] = $movement->id;

                        return $model::create($data);
                    })
            ])
            ->actions([
                \Filament\Actions\Action::make('Movement')
                    ->translateLabel()
                    ->color('gray')
                    // ->icon('heroicon-m-eye')
                    ->icon('heroicon-s-arrow-up-tray')
                    ->url(function (\App\Models\MovementItem $record) {
                        return url('/movements/' . $record->movement_id . '/view');
                    })->openUrlInNewTab(),
                \Filament\Actions\ViewAction::make()
                    ->beforeFormFilled(fn (array $data, string $model, MovementItem $movementItem) => self::ActionsBeforeFormFilled($data, $model, $movementItem)),
                \Filament\Actions\EditAction::make()
                    ->beforeFormFilled(fn (array $data, string $model, MovementItem $movementItem) => self::ActionsBeforeFormFilled($data, $model, $movementItem))
                    ->using(function (array $data, $action, MovementItem $movementItem): Model | bool {
                        $movement = \App\Models\Movement::find($movementItem->movement_id);
                        $movement->update($data);
                        $movementItem->update($data);
                        return $movementItem;
                    }),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\DeleteAction::make()->hidden(fn (MovementItem $movementItem) => !$movementItem->isLast()),
                ])
            ])
            ->bulkActions([
                // \Filament\Actions\BulkActionGroup::make([
                //     \Filament\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
