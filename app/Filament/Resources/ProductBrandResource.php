<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBrandResource\Pages;
use App\Filament\Resources\ProductBrandResource\RelationManagers\ProductModelsRelationManager;
use App\Models\ProductBrand;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductBrandResource extends Resource
{
    protected static ?string $model = ProductBrand::class;

    protected static bool $isScopedToTenant = false;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    // protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        // return (__('Product brand'));
        return __('Brand').' / '.__('Models');
    }

    public static function getPluralModelLabel(): string
    {
        // return (__('Product brands'));
        return __('Brands').' / '.__('Models');
    }

    public static function getFormDefinition()
    {
        return [
            TextInput::make('name')
                ->translateLabel()
                ->unique(ignoreRecord: true),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(self::getFormDefinition());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->searchable(isGlobal: true)
                    ->sortable()
                    ->translateLabel(),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistSearchInSession()
            ->filters([])
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can('update', ProductBrand::class)) {
                    return Pages\EditProductBrand::getUrl([$record->id]);
                }

                return Pages\ViewProductBrand::getUrl([$record->id]);
            })
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductModelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductBrands::route('/'),
            'create' => Pages\CreateProductBrand::route('/create'),
            'edit' => Pages\EditProductBrand::route('/{record}/edit'),
            'view' => Pages\ViewProductBrand::route('/{record}/view'),
        ];
    }
}
