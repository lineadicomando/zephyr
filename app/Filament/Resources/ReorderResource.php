<?php

namespace App\Filament\Resources;

use App\Contracts\ScopeContext;
use App\Filament\Resources\ReorderResource\Pages;
use App\Filament\Resources\ReorderResource\RelationManagers;
use App\Models\Reorder;
use App\Models\Stock;
use App\Services\Reorders\ReorderProposalService;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReorderResource extends Resource
{
    protected static ?string $model = Reorder::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return (__('Reorder'));
    }

    public static function getPluralModelLabel(): string
    {
        return (__('Reorders'));
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadge(): ?string
    {
        $stockTable = app(Stock::class)->getTable();
        $query =  static::getModel()::join($stockTable, $stockTable . '.id', '=', 'stock_id')
            ->whereColumn("{$stockTable}.stock", '<', 'reorder_point');

        $count = $query->count();
        return $count > 0 ? $count : null;
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Hidden::make('scope_id')
                    ->default(fn (): ?int => app(ScopeContext::class)->activeScopeId())
                    ->dehydrated(),
                Select::make('stock_id')
                    ->translateLabel()
                    ->required()
                    ->searchable()
                    ->relationship('stock', 'inventory_summary'),
                TextInput::make('reorder_point')
                    ->required()
                    ->translateLabel()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('reorder_quantity')
                    ->translateLabel()
                    ->numeric()
                    ->minValue(1),
                DatePicker::make('last_reorder_date')
                    ->translateLabel(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stock.path')
                    ->label('Position')
                    ->translateLabel(),
                TextColumn::make('stock.inventory_summary')
                    ->label('Inventory')
                    ->translateLabel(),
                TextColumn::make('stock.stock')
                    ->badge()
                    ->icon(function (String $state, Reorder $reorder): String {
                        if ($state < $reorder->reorder_point) {
                            return "heroicon-o-exclamation-triangle";
                        }
                        if ($state == $reorder->reorder_point) {
                            return "heroicon-o-bell-alert";
                        }
                        if ($state > $reorder->reorder_point) {
                            return "heroicon-o-check-circle";
                        }
                        return "heroicon-o-rectangle-stack";
                    })
                    ->color(function (String $state, Reorder $reorder): String {
                        if ($state < $reorder->reorder_point) {
                            return "danger";
                        }
                        if ($state == $reorder->reorder_point) {
                            return "warning";
                        }
                        if ($state > $reorder->reorder_point) {
                            return "success";
                        }
                        return "gray";
                    })
                    ->label('Stock')
                    ->sortable()
                    ->translateLabel(),
                TextColumn::make('reorder_point')
                    ->translateLabel(),
                TextColumn::make('reorder_quantity')
                    ->translateLabel(),
                TextColumn::make('last_reorder_date')
                    ->date('j M Y')
                    ->translateLabel(),
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
                Tables\Filters\SelectFilter::make('stock_level')
                    ->label('Status')
                    ->options([
                        'critical' => 'critical',
                        'warning' => 'warning',
                        'ok' => 'ok',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $stockTable = app(Stock::class)->getTable();
                        return match ($data['value'] ?? null) {
                            'critical' => $query->join($stockTable, $stockTable . '.id', '=', 'stock_id')
                                ->whereColumn("{$stockTable}.stock", '<', 'reorder_point')
                                ->select('reorders.*'),
                            'warning' => $query->join($stockTable, $stockTable . '.id', '=', 'stock_id')
                                ->whereColumn("{$stockTable}.stock", '=', 'reorder_point')
                                ->select('reorders.*'),
                            'ok' => $query->join($stockTable, $stockTable . '.id', '=', 'stock_id')
                                ->whereColumn("{$stockTable}.stock", '>', 'reorder_point')
                                ->select('reorders.*'),
                            default => $query,
                        };
                    }),
            ])
            ->persistFiltersInSession()
            ->headerActions([
                \Filament\Actions\Action::make('generateCriticalProposal')
                    ->label(__('Generate proposal'))
                    ->icon('heroicon-o-plus')
                    ->action(function () {
                        app(ReorderProposalService::class)->createDraftFromCritical(auth()->id());
                    })
                    ->requiresConfirmation(),
            ])
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can('update', Reorder::class)) {
                    return Pages\EditReorder::getUrl([$record->id]);
                }
                return Pages\ViewReorder::getUrl([$record->id]);
            })
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
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
            'index' => Pages\ListReorders::route('/'),
            'create' => Pages\CreateReorder::route('/create'),
            'edit' => Pages\EditReorder::route('/{record}/edit'),
            'view' => Pages\ViewReorder::route('/{record}/view'),
        ];
    }
}
