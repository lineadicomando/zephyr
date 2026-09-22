<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovementItemResource\Pages;
use App\Models\MovementItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MovementItemResource extends Resource
{
    protected static ?string $model = MovementItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('Details/Export');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Details/Export');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movement.id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('movement.date')
                    ->label('Date')
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
                TextColumn::make('movement.description')
                    ->label('Description')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('inventory.inventory_number')
                    ->label('Inventory')
                    ->translateLabel()
                    ->searchable(),
                TextColumn::make('inventory.product.product_group.name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventory.product.product_type.name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventory.product.product_brand.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventory.product.product_model.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventory.product.name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventory.serial_number')
                    ->label('Serial Number')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('inventory.description')
                    ->label('Inventory description')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Qty')
                    ->translateLabel()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('movement.note')
                    ->label('Note')
                    ->translateLabel()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->filters([
                SelectFilter::make('movement_type')
                    ->translateLabel()
                    ->preload()
                    ->searchable()
                    ->relationship('movement.movement_type', 'name'),
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
                SelectFilter::make('product_group')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('inventory.product.product_group', 'name'),
                SelectFilter::make('product_type')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('inventory.product.product_type', 'name'),
                SelectFilter::make('product')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('inventory.product', 'name'),
                SelectFilter::make('inventory')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('inventory', 'summary'),
            ])
            ->actions([
                // \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn (MovementItem $record): bool => $record->isLast() && auth()->user()->can('delete', $record)),
                ]),
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
            'index' => Pages\ListMovementItems::route('/'),
        ];
    }
}
