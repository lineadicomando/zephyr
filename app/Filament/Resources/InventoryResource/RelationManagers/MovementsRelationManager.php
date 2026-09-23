<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Filament\Resources\MovementResource;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\Stock;
use App\Services\Stocks\StockAvailabilityService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

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
                DateTimePicker::make('date')
                    ->required()
                    ->disabled(fn ($record) => ! is_null($record))
                    ->default(fn (): string => now()->format('Y-m-d H:i'))
                    ->seconds(false)
                    ->translateLabel(),
                Select::make('movement_type_id')
                    ->label('Movement type')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->translateLabel()
                    ->options(MovementType::all()->sortBy('name')->pluck('name', 'id')),
                Select::make('from_inventory_position_id')
                    ->label('Origin position')
                    ->disabled(fn ($record) => ! is_null($record))
                    ->translateLabel()
                    ->searchable()
                    ->live()
                    ->preload()
                    ->options(function () use ($ownerRecord) {
                        $query = Stock::query()
                            ->select('path', 'inventory_position_id as id')
                            ->where('stock', '>', 0)
                            ->where('inventory_id', $ownerRecord->id);

                        return $query->get()->sortBy('path')->pluck('path', 'id');
                    }),
                Select::make('to_inventory_position_id')
                    ->label('Destination position')
                    ->disabled(fn ($record) => ! is_null($record))
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->options(function (Get $get) {
                        $query = InventoryPosition::query()->select('path', 'id')->where('id', '<>', $get('from_inventory_position_id'));

                        return $query->get()->sortBy('path')->pluck('path', 'id');
                    }),
                TextInput::make('stock')
                    ->label('Qty')
                    ->disabled(fn ($record) => ! is_null($record))
                    ->default(1)
                    ->translateLabel()
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->rule(fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get, $ownerRecord): void {
                        $positionId = filled($get('from_inventory_position_id')) ? (int) $get('from_inventory_position_id') : null;

                        if (is_numeric($value) && ! app(StockAvailabilityService::class)->canWithdraw($ownerRecord->id, $positionId, (int) $value)) {
                            $fail(__('Insufficient availability, impossible to proceed'));
                        }
                    }),
                TextInput::make('description')
                    ->translateLabel()
                    ->maxLength(255)
                    ->required(),
                Textarea::make('note')->translateLabel(),
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
                    ->relationship('movement.to_inventory_position', 'path'),
            ])->filtersFormColumns(2)
            ->headerActions([
                CreateAction::make()
                    // The action creates a Movement too, not only the MovementItem checked by the relation manager.
                    ->visible(fn (): bool => auth()->user()->can('create', Movement::class))
                    ->using(function (array $data, CreateAction $action, string $model): Model {
                        $data['inventory_id'] = $this->getOwnerRecord()->getKey();
                        $positionId = filled($data['from_inventory_position_id'] ?? null) ? (int) $data['from_inventory_position_id'] : null;

                        try {
                            return app(StockAvailabilityService::class)->withdraw(
                                inventoryId: $data['inventory_id'],
                                positionId: $positionId,
                                quantity: (int) $data['stock'],
                                callback: function () use ($data, $model): Model {
                                    $data['movement_id'] = Movement::create($data)->getKey();

                                    return $model::create($data);
                                },
                            );
                        } catch (ValidationException) {
                            Notification::make()
                                ->warning()
                                ->title(__('Warning'))
                                ->body(__('Insufficient availability, impossible to proceed'))
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->actions([
                Action::make('Movement')
                    ->translateLabel()
                    ->color('gray')
                    // ->icon('heroicon-m-eye')
                    ->icon('heroicon-s-arrow-up-tray')
                    ->url(function (MovementItem $record) {
                        return MovementResource::getUrl('view', ['record' => $record->movement_id]);
                    })->openUrlInNewTab(),
                ViewAction::make()
                    ->beforeFormFilled(fn (array $data, string $model, MovementItem $movementItem) => self::ActionsBeforeFormFilled($data, $model, $movementItem)),
                EditAction::make()
                    // The action updates the Movement of the item.
                    ->visible(fn (MovementItem $movementItem): bool => auth()->user()->can('update', $movementItem->movement))
                    ->beforeFormFilled(fn (array $data, string $model, MovementItem $movementItem) => self::ActionsBeforeFormFilled($data, $model, $movementItem))
                    ->using(function (array $data, MovementItem $movementItem): Model {
                        $movementItem->movement->update(Arr::only($data, ['movement_type_id', 'description', 'note']));

                        return $movementItem;
                    }),
                ActionGroup::make([
                    DeleteAction::make()->hidden(fn (MovementItem $movementItem) => ! $movementItem->isLast()),
                ]),
            ])
            ->bulkActions([
                // \Filament\Actions\BulkActionGroup::make([
                //     \Filament\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
