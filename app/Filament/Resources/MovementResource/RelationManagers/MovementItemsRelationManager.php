<?php

namespace App\Filament\Resources\MovementResource\RelationManagers;

use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\Stock;
use App\Services\Stocks\StockAvailabilityService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LogicException;

class MovementItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'movement_items';

    /**
     * The movement whose items are listed.
     */
    protected function movement(): Movement
    {
        $movement = $this->getOwnerRecord();

        if (! $movement instanceof Movement) {
            throw new LogicException('The movement items relation manager belongs to a movement.');
        }

        return $movement;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Movement Items');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('inventory_id')
                    ->translateLabel()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (! is_null($this->movement()->from_inventory_position_id)) {
                            $availability = Stock::findAvailability(
                                inventoryId: $state,
                                positionId: $this->movement()->from_inventory_position_id
                            );
                            $set('availability', $availability);
                        }
                    })
                    ->relationship('inventory', 'summary', modifyQueryUsing: function (Builder $query) {
                        if (! is_null($this->movement()->from_inventory_position_id)) {
                            $query->join('stocks', 'inventories.id', '=', 'stocks.inventory_id');
                            $query->where('stocks.stock', '>', '0');
                            $query->where('stocks.inventory_position_id', $this->movement()->from_inventory_position_id);
                        }

                        return $query;
                    })
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
                    ->integer()
                    ->minValue(1)
                    ->rule(fn (Get $get): Closure => $this->withdrawalRule(
                        filled($get('inventory_id')) ? (int) $get('inventory_id') : null,
                    )),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withIsLast())
            ->recordTitleAttribute('inventory_summary')
            ->columns([
                TextColumn::make('inventory_summary')
                    ->searchable()
                    ->translateLabel(),
                TextColumn::make('outcoming_stock.stock')
                    ->label('Residual availability')
                    ->translateLabel(),
                TextColumn::make('stock')
                    ->translateLabel()
                    ->label('Qty'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data, CreateAction $action): MovementItem => $this->withdraw(
                        action: $action,
                        inventoryId: (int) $data['inventory_id'],
                        quantity: (int) $data['stock'],
                        callback: fn (): MovementItem => $this->movement()->movement_items()->create($data),
                    )),
            ])
            ->actions([
                DeleteAction::make()
                    ->hidden(fn (MovementItem $movementItem) => ! $movementItem->isLast())
                    ->label(''),
                EditAction::make()
                    ->hidden(fn (MovementItem $movementItem) => ! $movementItem->isLast())
                    ->label('')
                    ->form(fn (MovementItem $record): array => [
                        TextInput::make('stock')
                            ->label('Qty')
                            ->default(1)
                            ->translateLabel()
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->rule(fn (): Closure => $this->withdrawalRule($record->inventory_id, $record)),
                    ])
                    ->using(fn (array $data, EditAction $action, MovementItem $record): MovementItem => $this->withdraw(
                        action: $action,
                        inventoryId: $record->inventory_id,
                        quantity: (int) $data['stock'],
                        alreadyWithdrawn: $this->alreadyWithdrawn($record),
                        callback: function () use ($data, $record): MovementItem {
                            $record->update(['stock' => (int) $data['stock']]);

                            return $record;
                        },
                    )),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn (MovementItem $record): bool => $record->isLast() && auth()->user()->can('delete', $record)),
                ]),
            ]);
    }

    protected function withdrawalRule(?int $inventoryId, ?MovementItem $record = null): Closure
    {
        $positionId = $this->movement()->from_inventory_position_id;

        return function (string $attribute, mixed $value, Closure $fail) use ($inventoryId, $positionId, $record): void {
            if ($inventoryId === null || ! is_numeric($value)) {
                return;
            }

            if (! app(StockAvailabilityService::class)->canWithdraw($inventoryId, $positionId, (int) $value, $this->alreadyWithdrawn($record))) {
                $fail(__('Insufficient availability, impossible to proceed'));
            }
        };
    }

    protected function alreadyWithdrawn(?MovementItem $record): int
    {
        return filled($record?->outcoming_stock_id) ? (int) $record->getOriginal('stock') : 0;
    }

    /**
     * @param  Closure(): MovementItem  $callback
     */
    protected function withdraw(Action $action, int $inventoryId, int $quantity, Closure $callback, int $alreadyWithdrawn = 0): MovementItem
    {
        try {
            return app(StockAvailabilityService::class)->withdraw(
                inventoryId: $inventoryId,
                positionId: $this->movement()->from_inventory_position_id,
                quantity: $quantity,
                callback: $callback,
                alreadyWithdrawn: $alreadyWithdrawn,
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
    }
}
