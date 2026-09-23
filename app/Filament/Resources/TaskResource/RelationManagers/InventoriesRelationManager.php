<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Filament\Tables\Filters\InventoryFilters;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InventoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inventories';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Inventories');
    }

    // public function form(Schema $schema): Schema
    // {
    //     return $form
    //         ->schema([
    //             TextInput::make('note')->translateLabel()->columnSpanFull(),
    //         ]);
    // }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('summary')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('inventory_number')
                    ->label('Inventory')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.product_group.name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.product_type.name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.product_brand.name')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.product_model.name')
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
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->translateLabel()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                TextColumn::make('mac_address')
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

                // Tables\Columns\TextColumn::make('summary')
                // ->translateLabel()
                // ->searchable()
                // ->sortable(),
            ])
            ->filters([
                ...InventoryFilters::make(stockRelation: 'non_zero_stocks', productRelation: 'product'),
            ])
            ->filtersFormColumns(2)
            // ->filtersFormWidth(MaxWidth::Small)
            ->filtersFormMaxHeight('350px')
            ->headerActions([
                // \Filament\Actions\CreateAction::make(),
                AttachAction::make()
                    // ->recordSelectSearchColumns(['summary'])
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()->multiple(),
                        // TextInput::make('note')
                        //     ->columnSpanFull()
                        //     ->translateLabel(),
                    ]),
            ])
            ->actions([
                // \Filament\Actions\EditAction::make(),
                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
