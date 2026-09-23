<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        return __('Tag');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tags');
    }

    public static function getFormDefinition(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->translateLabel()
                ->unique(ignoreRecord: true),
            ColorPicker::make('color')
                ->translateLabel(),
        ];
    }

    /**
     * Select of the tags of a product or of an inventory.
     */
    public static function getRelationshipSelect(): Select
    {
        return Select::make('tags')
            ->translateLabel()
            ->relationship('tags', 'name')
            ->multiple()
            ->searchable()
            ->preload()
            ->createOptionForm(auth()->user()?->can('create', Tag::class) ? self::getFormDefinition() : null);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(self::getFormDefinition());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->badge()
                    ->color(fn (Tag $record) => $record->color ? Color::hex($record->color) : 'gray')
                    ->searchable(isGlobal: true)
                    ->sortable()
                    ->translateLabel(),
                ColorColumn::make('color')->translateLabel(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->translateLabel(),
                TextColumn::make('inventories_count')
                    ->counts('inventories')
                    ->label('Inventories')
                    ->translateLabel(),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->persistSearchInSession()
            ->recordUrl(function ($record) {
                if (auth()->user()->can('update', Tag::class)) {
                    return Pages\EditTag::getUrl(['record' => $record]);
                }

                return Pages\ViewTag::getUrl(['record' => $record]);
            })
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
            'view' => Pages\ViewTag::route('/{record}/view'),
        ];
    }
}
