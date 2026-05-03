<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductGroupResource\Pages;
use App\Filament\Resources\ProductGroupResource\RelationManagers;
use App\Models\ProductGroup;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductGroupResource extends Resource
{
    protected static ?string $model = ProductGroup::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\BackedEnum|null $navigationIcon = "heroicon-o-rectangle-group";
    protected static ?string $recordTitleAttribute = "name";
    protected static string|\UnitEnum|null $navigationGroup = "Products";

    // protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getNavigationLabel(): string
    {
        return __('Groups');
    }

    public static function getModelLabel(): string
    {
        return __("Product group");
    }

    public static function getPluralModelLabel(): string
    {
        return __("Product groups");
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make("name")
                ->translateLabel()
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")
                    ->label("#")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("name")
                    ->searchable(isGlobal: true)
                    ->sortable()
                    ->translateLabel(),
                TextColumn::make("created_at")
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("updated_at")
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistSearchInSession()
            ->filters([
                //
            ])
            ->actions([\Filament\Actions\EditAction::make()])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            "index" => Pages\ListProductGroups::route("/"),
            "create" => Pages\CreateProductGroup::route("/create"),
            "edit" => Pages\EditProductGroup::route("/{record}/edit"),
        ];
    }
}
