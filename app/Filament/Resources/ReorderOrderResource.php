<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReorderOrderResource\Pages;
use App\Filament\Resources\ReorderOrderResource\RelationManagers\ItemsRelationManager;
use App\Models\ReorderOrder;
use App\Services\Reorders\ReorderOrderService;
use App\Services\Reorders\ReorderProposalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReorderOrderResource extends Resource
{
    protected static ?string $model = ReorderOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return __('Reorder order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Reorder orders');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Textarea::make('notes')->translateLabel()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('items_count')->counts('items')->label('Items')->translateLabel(),
                TextColumn::make('creator.name')->label('User')->translateLabel(),
                TextColumn::make('requested_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ordered_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('received_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->translateLabel()->sortable(),
                TextColumn::make('updated_at')->dateTime()->translateLabel()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(array_combine(ReorderOrder::statuses(), ReorderOrder::statuses())),
            ])
            ->headerActions([
                Action::make('generateCriticalProposal')
                    ->label(__('Generate proposal'))
                    ->icon('heroicon-o-plus')
                    ->authorize(fn (): bool => auth()->user()->can('create', ReorderOrder::class))
                    ->action(function (): void {
                        $order = app(ReorderProposalService::class)->createDraftFromCritical(auth()->id());

                        if ($order === null) {
                            Notification::make()
                                ->info()
                                ->title(__('No critical items to reorder'))
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->recordUrl(function ($record) {
                if (static::canEdit($record)) {
                    return Pages\EditReorderOrder::getUrl([$record->id]);
                }

                return Pages\ViewReorderOrder::getUrl([$record->id]);
            })
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (ReorderOrder $record): bool => static::canEdit($record)),
                Action::make('request')
                    ->authorize('transition')
                    ->label(__('Request'))
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (ReorderOrder $record): bool => $record->status === ReorderOrder::STATUS_DRAFT)
                    ->action(fn (ReorderOrder $record) => app(ReorderOrderService::class)->request($record, auth()->id()))
                    ->requiresConfirmation(),
                Action::make('markOrdered')
                    ->authorize('transition')
                    ->label(__('Mark ordered'))
                    ->icon('heroicon-o-truck')
                    ->visible(fn (ReorderOrder $record): bool => $record->status === ReorderOrder::STATUS_REQUESTED)
                    ->action(fn (ReorderOrder $record) => app(ReorderOrderService::class)->markOrdered($record, auth()->id()))
                    ->requiresConfirmation(),
                Action::make('markReceived')
                    ->authorize('transition')
                    ->label(__('Mark received'))
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (ReorderOrder $record): bool => $record->status === ReorderOrder::STATUS_ORDERED)
                    ->action(fn (ReorderOrder $record) => app(ReorderOrderService::class)->markReceived($record, auth()->id()))
                    ->requiresConfirmation(),
                Action::make('cancel')
                    ->authorize('transition')
                    ->label(__('Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ReorderOrder $record): bool => in_array($record->status, [
                        ReorderOrder::STATUS_DRAFT,
                        ReorderOrder::STATUS_REQUESTED,
                        ReorderOrder::STATUS_ORDERED,
                    ], true))
                    ->action(fn (ReorderOrder $record) => app(ReorderOrderService::class)->cancel($record, auth()->id()))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn (ReorderOrder $record): bool => $record->status === ReorderOrder::STATUS_DRAFT && auth()->user()->can('delete', $record)),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReorderOrders::route('/'),
            'create' => Pages\CreateReorderOrder::route('/create'),
            'edit' => Pages\EditReorderOrder::route('/{record}/edit'),
            'view' => Pages\ViewReorderOrder::route('/{record}/view'),
        ];
    }
}
