<?php

namespace App\Filament\Resources\ReorderOrderResource\RelationManagers;

use App\Models\ReorderOrder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Items');
    }

    public function table(Table $table): Table
    {
        /** @var ReorderOrder $owner */
        $owner = $this->getOwnerRecord();
        $isLocked = in_array($owner->status, [
            ReorderOrder::STATUS_ORDERED,
            ReorderOrder::STATUS_RECEIVED,
            ReorderOrder::STATUS_CANCELLED,
        ], true);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stock.path')->label('Position')->translateLabel(),
                Tables\Columns\TextColumn::make('stock.inventory_summary')->label('Inventory')->translateLabel(),
                Tables\Columns\TextColumn::make('current_stock')->label('Stock')->translateLabel(),
                Tables\Columns\TextColumn::make('reorder_point')->translateLabel(),
                Tables\Columns\TextColumn::make('suggested_qty')->translateLabel(),
                Tables\Columns\TextColumn::make('ordered_qty')->translateLabel(),
                Tables\Columns\TextColumn::make('received_qty')->translateLabel(),
            ])
            ->headerActions([])
            ->actions([
                \Filament\Actions\EditAction::make()->visible(! $isLocked),
            ])
            ->bulkActions([]);
    }
}
