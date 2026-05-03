<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Filament\Resources\InventoryLocationResource;
use App\Filament\Resources\InventoryPositionResource;
use App\Models\InventoryPosition;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';
    // protected static ?string $title = '';


    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Stocks');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('stock', '>', '0'))
            ->recordTitleAttribute('path')
            ->columns([
                Tables\Columns\TextColumn::make('inventory_position.path')
                    ->label('Position')
                    ->searchable()
                    ->sortable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('stock')
                    ->searchable()
                    ->sortable()
                    ->translateLabel(),
            ])
            ->filters([
                SelectFilter::make('inventory_position_id')
                    ->label('Position')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('inventory_position', 'path'),
            ])
            ->headerActions([
                // \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                // \Filament\Actions\EditAction::make(),
                // \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // \Filament\Actions\BulkActionGroup::make([
                //     \Filament\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
