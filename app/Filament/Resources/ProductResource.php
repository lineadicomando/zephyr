<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static bool $shouldRegisterNavigation = true;

    // protected static string|\UnitEnum|null $navigationGroup = 'Products';

    // public static function getNavigationGroup(): ?string
    // {
    //     return __(static::$navigationGroup);
    // }
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Products');
    }

    public static function getFormDefinition()
    {
        $user = auth()->user();

        return [
            Select::make('product_group_id')
                ->label('Group')
                ->required()
                ->translateLabel()
                ->searchable()
                ->preload()
                ->relationship('product_group', 'name'),
            Select::make('product_type_id')
                ->label('Type')
                ->required()
                ->translateLabel()
                ->searchable()
                ->preload()
                ->relationship('product_type', 'name'),
            Select::make('product_brand_id')
                ->label('Brand')
                ->translateLabel()
                ->searchable()
                ->preload()
                ->relationship('product_brand', 'name')
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state) {
                    $set('product_model_id', '');
                })
                ->createOptionForm(
                    $user?->can('create', ProductBrand::class)
                        ? ProductBrandResource::getFormDefinition()
                        : null,
                )
                ->editOptionForm(
                    $user?->can('update', ProductBrand::class)
                        ? ProductBrandResource::getFormDefinition()
                        : null,
                ),
            Select::make('product_model_id')
                ->label('Model')
                ->translateLabel()
                ->searchable()
                ->preload()
                ->live()
                ->helperText(function (Get $get): string {
                    return filled($get('product_brand_id')) &&
                        blank($get('product_model_id'))
                        ? __('Active selection.')
                        : '';
                })
                ->relationship(
                    name: 'product_model',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (
                        Builder $query,
                        Set $set,
                        Get $get,
                    ) {
                        $productBrandId = $get('product_brand_id');
                        $producModelId = $get('product_model_id');

                        if (! empty($productBrandId) && empty($producModelId)) {
                            return $query->where(
                                'product_brand_id',
                                $productBrandId,
                            );
                        }

                        if (! empty($producModelId)) {
                            $productModel = ProductModel::find($producModelId);
                            if ($productModel) {
                                $set(
                                    'product_brand_id',
                                    $productModel->product_brand_id,
                                );
                            }
                        }

                        return $query;
                    },
                )
                ->createOptionForm(
                    $user?->can('create', ProductModel::class)
                        ? ProductModelResource::getFormDefinition()
                        : null,
                )
                ->editOptionForm(
                    $user?->can('update', ProductModel::class)
                        ? ProductModelResource::getFormDefinition()
                        : null,
                ),
            TextInput::make('code')->translateLabel(),
            TextInput::make('name')->required()->translateLabel(),
            Textarea::make('note')->translateLabel(),
        ];
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
                TextColumn::make('product_group.name')
                    ->label('Group')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_type.name')
                    ->label('Type')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_brand.name')
                    ->label('Brand')
                    ->translateLabel()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product_model.name')
                    ->label('Model')
                    ->translateLabel()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('code')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->filters([
                SelectFilter::make('product_group')
                    ->label('Group')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_group', 'name'),
                SelectFilter::make('product_type')
                    ->label('Type')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_type', 'name'),
                SelectFilter::make('product_brand')
                    ->label('Brand')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_brand', 'name'),
                SelectFilter::make('product_model')
                    ->label('Model')
                    ->translateLabel()
                    ->searchable()
                    ->preload()
                    ->relationship('product_model', 'name'),
            ])
            ->persistFiltersInSession()
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can('update', $record)) {
                    return Pages\EditProduct::getUrl(['record' => $record]);
                }

                return Pages\ViewProduct::getUrl(['record' => $record]);
            })
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn (Product $record): bool => ! $record->hasRelated() && auth()->user()->can('delete', $record)),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}/view'),
        ];
    }
}
