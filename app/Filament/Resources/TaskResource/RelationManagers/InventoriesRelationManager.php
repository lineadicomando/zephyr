<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Filament\Forms\ChecklistForm;
use App\Filament\Forms\Components\BarcodeScannerInput;
use App\Filament\Tables\Filters\InventoryFilters;
use App\Models\Inventory;
use App\Models\Task;
use App\Models\TaskInventory;
use App\Services\Checklists\ChecklistService;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inventories';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
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
                    ->searchable(isIndividual: true)
                    ->sortable(),
                // Hidden with a checklist, to keep the checklist column and
                // actions in view on small screens.
                TextColumn::make('description')
                    ->translateLabel()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: $this->hasChecklist())
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->translateLabel()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: $this->hasChecklist())
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
                TextColumn::make('position_path')
                    ->label('Position in the task')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checklist')
                    ->label('Checklist')
                    ->translateLabel()
                    ->badge()
                    ->visible(fn (): bool => $this->hasChecklist())
                    ->state(fn (Inventory $record): string => match (true) {
                        $this->pivot($record)?->completed_at === null => __('To do'),
                        (bool) $this->pivot($record)->has_anomalies => __('Anomalies'),
                        default => __('OK'),
                    })
                    ->color(fn (Inventory $record): string => match (true) {
                        $this->pivot($record)?->completed_at === null => 'gray',
                        (bool) $this->pivot($record)->has_anomalies => 'danger',
                        default => 'success',
                    })
                    ->description(fn (Inventory $record): ?string => $this->pivot($record)?->completed_at?->translatedFormat(config('app.datetime_format'))),

                // Tables\Columns\TextColumn::make('summary')
                // ->translateLabel()
                // ->searchable()
                // ->sortable(),
            ])
            ->filters([
                ...InventoryFilters::make(stockRelation: 'non_zero_stocks', productRelation: 'product'),
            ])
            ->filtersFormColumns(2)
            // ->filtersFormWidth(MaxWidth::Small)
            ->filtersFormMaxHeight('350px')
            ->headerActions([
                $this->scanChecklistAction(),
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
                $this->fillChecklistAction(),
                $this->viewChecklistAction(),
                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    protected function task(): Task
    {
        /** @var Task $task */
        $task = $this->getOwnerRecord();

        return $task;
    }

    /**
     * Row of the task_inventory table of the inventory, loaded with the table records.
     */
    protected function pivot(Inventory $inventory): ?TaskInventory
    {
        $pivot = $inventory->relationLoaded('pivot') ? $inventory->getRelation('pivot') : null;

        return $pivot instanceof TaskInventory ? $pivot : null;
    }

    protected function hasChecklist(): bool
    {
        return $this->task()->checklist_template_id !== null;
    }

    protected function canFillChecklists(): bool
    {
        return $this->hasChecklist() && (bool) auth()->user()?->can('fillChecklist', $this->task());
    }

    protected function taskInventory(Inventory $inventory): TaskInventory
    {
        return TaskInventory::query()
            ->with('results')
            ->where('task_id', $this->task()->getKey())
            ->where('inventory_id', $inventory->getKey())
            ->firstOrFail();
    }

    /**
     * @return list<Section>
     */
    protected function checklistSchema(Inventory $inventory): array
    {
        return ChecklistForm::schema(
            app(ChecklistService::class)->applicableItems($this->task()->checklist_template, $inventory),
        );
    }

    /**
     * Fill the checklist of the inventory: only the technician assigned to
     * the task and the admins.
     */
    protected function fillChecklistAction(): Action
    {
        return Action::make('fillChecklist')
            ->label('Fill')
            ->translateLabel()
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('primary')
            ->slideOver()
            ->visible(fn (): bool => $this->canFillChecklists())
            ->authorize(fn (): bool => $this->canFillChecklists())
            ->modalHeading(fn (Inventory $record): string => __('Checklist').': '.$record->summary)
            ->modalDescription(fn (Inventory $record): ?string => $this->pivot($record)?->position_path)
            ->fillForm(fn (Inventory $record): array => ChecklistForm::fill($this->taskInventory($record)))
            ->schema(fn (Inventory $record): array => $this->checklistSchema($record))
            ->action(function (array $data, Inventory $record): void {
                $taskInventory = app(ChecklistService::class)->save(
                    $this->taskInventory($record),
                    ChecklistForm::answers($data),
                    ['note' => $data['note'] ?? null, 'photos' => $data['photos'] ?? []],
                    auth()->id(),
                );

                Notification::make()
                    ->title($taskInventory->has_anomalies ? __('Checklist saved with anomalies') : __('Checklist saved'))
                    ->color($taskInventory->has_anomalies ? 'danger' : 'success')
                    ->icon($taskInventory->has_anomalies ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                    ->send();
            });
    }

    /**
     * Read only checklist for the users who cannot fill it.
     */
    protected function viewChecklistAction(): Action
    {
        return Action::make('viewChecklist')
            ->label('Checklist')
            ->translateLabel()
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color('gray')
            ->slideOver()
            ->visible(fn (Inventory $record): bool => $this->hasChecklist() && ! $this->canFillChecklists() && $this->pivot($record)?->completed_at !== null)
            ->modalHeading(fn (Inventory $record): string => __('Checklist').': '.$record->summary)
            ->modalDescription(fn (Inventory $record): ?string => $this->pivot($record)?->position_path)
            ->fillForm(fn (Inventory $record): array => ChecklistForm::fill($this->taskInventory($record)))
            ->schema(fn (Inventory $record): array => $this->checklistSchema($record))
            ->disabledForm()
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'));
    }

    /**
     * Scan the inventory number or the serial number of an inventory of the
     * task to open its checklist.
     */
    protected function scanChecklistAction(): Action
    {
        return Action::make('scanChecklist')
            ->label('Scan')
            ->translateLabel()
            ->icon(Heroicon::OutlinedQrCode)
            ->visible(fn (): bool => $this->canFillChecklists())
            ->authorize(fn (): bool => $this->canFillChecklists())
            ->modalWidth('md')
            ->modalSubmitActionLabel(__('Open checklist'))
            ->schema([
                BarcodeScannerInput::make('code')
                    ->label('Inventory number or serial number')
                    ->translateLabel()
                    ->required()
                    ->autofocus(),
            ])
            ->action(function (array $data, Action $action): void {
                $code = trim((string) $data['code']);

                $inventory = $this->task()->inventories()
                    ->where(fn (Builder $query): Builder => $query
                        ->where('inventories.inventory_number', $code)
                        ->orWhere('inventories.serial_number', $code))
                    ->first();

                if ($inventory === null) {
                    Notification::make()
                        ->title(__('No inventory of the task matches the code.'))
                        ->danger()
                        ->send();

                    $action->halt();
                }

                $this->replaceMountedAction('fillChecklist', context: [
                    'table' => true,
                    'recordKey' => (string) $inventory->getKey(),
                ]);
            });
    }
}
