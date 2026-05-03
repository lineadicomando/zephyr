<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\AttachAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class InventoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inventories';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
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
                    ->searchable(isIndividual: true,)
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
                \Filament\Actions\DetachAction::make()
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DetachBulkAction::make()
                ]),
            ]);
    }
}
