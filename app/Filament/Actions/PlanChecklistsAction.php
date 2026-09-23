<?php

namespace App\Filament\Actions;

use App\Enums\ChecklistGrouping;
use App\Models\ChecklistTemplate;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Services\Checklists\ChecklistPlanningService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Wizard creating the tasks of a checklist for many inventories: pick the
 * template, select the inventories by category or one by one, split them
 * into tasks by location or position and assign a technician to each task.
 */
class PlanChecklistsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'planChecklists';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Plan checklists'))
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('gray')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel(__('Create tasks'))
            ->visible(fn (): bool => auth()->user()->can('create', Task::class) && auth()->user()->isAdmin())
            ->authorize(fn (): bool => auth()->user()->can('create', Task::class) && auth()->user()->isAdmin())
            ->steps([
                $this->taskStep(),
                $this->inventoriesStep(),
                $this->techniciansStep(),
            ])
            ->action(function (array $data): void {
                $tasks = app(ChecklistPlanningService::class)->createTasks(self::scopeId(), $data, $data['groups'] ?? []);

                Notification::make()
                    ->title(trans_choice('{0} No task created|{1} :count task created|[2,*] :count tasks created', $tasks->count()))
                    ->color($tasks->isEmpty() ? 'warning' : 'success')
                    ->send();
            });
    }

    protected static function scopeId(): int
    {
        return (int) filament()->getTenant()?->getKey();
    }

    protected function taskStep(): Step
    {
        return Step::make(__('Task'))
            ->schema([
                Select::make('checklist_template_id')
                    ->label('Checklist template')
                    ->translateLabel()
                    ->options(fn (): array => ChecklistTemplate::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                        if (blank($get('description'))) {
                            $set('description', ChecklistTemplate::query()->whereKey($state)->value('name'));
                        }

                        if (blank($get('task_type_id'))) {
                            $set('task_type_id', TaskType::query()->where('checklist_template_id', $state)->value('id'));
                        }
                    }),
                Grid::make(2)
                    ->schema([
                        Select::make('task_type_id')
                            ->label('Type')
                            ->translateLabel()
                            ->options(fn (): array => TaskType::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        Select::make('task_status_id')
                            ->label('Status')
                            ->translateLabel()
                            ->options(fn (): array => TaskStatus::query()->where('completed', false)->orderBy('order')->pluck('name', 'id')->all())
                            ->default(fn (): ?int => TaskStatus::getDefaultId())
                            ->required(),
                        DateTimePicker::make('starts_at')
                            ->translateLabel()
                            ->seconds(false)
                            ->default(now()->format('Y-m-d H:i'))
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->translateLabel()
                            ->seconds(false)
                            ->afterOrEqual('starts_at'),
                    ]),
                TextInput::make('description')
                    ->translateLabel()
                    ->helperText(__('With more tasks, the location or position is appended.'))
                    ->required(),
                Textarea::make('note')
                    ->translateLabel(),
            ]);
    }

    protected function inventoriesStep(): Step
    {
        return Step::make(__('Inventories'))
            ->schema([
                Text::make(__('The inventories matching every filled filter, plus the ones selected one by one.')),
                Grid::make(2)
                    ->schema([
                        self::multipleSelect('tag_ids', 'Tags', fn (): array => Tag::query()->orderBy('name')->pluck('name', 'id')->all()),
                        self::multipleSelect('location_ids', 'Location', fn (): array => InventoryLocation::withoutGlobalScopes()->where('scope_id', self::scopeId())->orderBy('name')->pluck('name', 'id')->all()),
                        self::multipleSelect('product_group_ids', 'Group', fn (): array => ProductGroup::query()->orderBy('name')->pluck('name', 'id')->all()),
                        self::multipleSelect('product_type_ids', 'Type', fn (): array => ProductType::query()->orderBy('name')->pluck('name', 'id')->all()),
                        self::multipleSelect('product_model_ids', 'Model', fn (): array => ProductModel::query()->orderBy('name')->pluck('name', 'id')->all()),
                        Select::make('inventory_ids')
                            ->label('Inventories one by one')
                            ->translateLabel()
                            ->multiple()
                            ->searchable()
                            ->live()
                            ->getSearchResultsUsing(fn (string $search): array => Inventory::withoutGlobalScopes()
                                ->where('scope_id', self::scopeId())
                                ->where('summary', 'like', "%{$search}%")
                                ->orderBy('inventory_number')
                                ->limit(50)
                                ->pluck('summary', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Inventory::withoutGlobalScopes()
                                ->where('scope_id', self::scopeId())
                                ->whereKey($values)
                                ->pluck('summary', 'id')
                                ->all()),
                    ]),
                Select::make('grouping')
                    ->label('Split into')
                    ->translateLabel()
                    ->options(ChecklistGrouping::class)
                    ->default(ChecklistGrouping::Location)
                    ->required(),
                Text::make(fn (Get $get): string => trans_choice(
                    '{0} No inventory selected|{1} :count inventory selected|[2,*] :count inventories selected',
                    self::matchingInventories($get)->count(),
                )),
            ])
            ->afterValidation(function (Get $get, Set $set): void {
                $inventories = self::matchingInventories($get);

                if ($inventories->isEmpty()) {
                    Notification::make()
                        ->title(__('Select at least one inventory.'))
                        ->warning()
                        ->send();

                    throw new Halt;
                }

                $grouping = $get('grouping');
                $groups = app(ChecklistPlanningService::class)->groupInventories(
                    $inventories,
                    $grouping instanceof ChecklistGrouping ? $grouping : ChecklistGrouping::from((string) $grouping),
                );

                $set('groups', array_map(fn (array $group): array => [
                    'label' => $group['label'],
                    'available_ids' => $group['inventory_ids'],
                    'inventory_ids' => $group['inventory_ids'],
                    'user_id' => $get('default_user_id'),
                ], $groups));
            });
    }

    protected function techniciansStep(): Step
    {
        return Step::make(__('Technicians'))
            ->schema([
                Select::make('default_user_id')
                    ->label('Technician of every task')
                    ->translateLabel()
                    ->options(fn (): array => self::technicianOptions())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, mixed $state) => $set(
                        'groups',
                        array_map(fn (array $group): array => [...$group, 'user_id' => $state], $get('groups') ?? []),
                    )),
                Repeater::make('groups')
                    ->label('Tasks to create')
                    ->translateLabel()
                    ->addable(false)
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): string => (filled($state['label'] ?? null) ? $state['label'] : __('All the inventories'))
                        .' ('.count($state['inventory_ids'] ?? []).')')
                    ->collapsible()
                    ->schema([
                        Hidden::make('label'),
                        Hidden::make('available_ids'),
                        Select::make('user_id')
                            ->label('Technician')
                            ->translateLabel()
                            ->options(fn (): array => self::technicianOptions())
                            ->searchable()
                            ->required(),
                        CheckboxList::make('inventory_ids')
                            ->label('Inventories')
                            ->translateLabel()
                            ->options(fn (Get $get): array => Inventory::withoutGlobalScopes()
                                ->where('scope_id', self::scopeId())
                                ->whereKey($get('available_ids') ?? [])
                                ->orderBy('inventory_number')
                                ->pluck('summary', 'id')
                                ->all())
                            ->bulkToggleable()
                            ->required(),
                    ]),
            ]);
    }

    /**
     * @param  Closure(): array<int|string, string>  $options
     */
    protected static function multipleSelect(string $name, string $label, Closure $options): Select
    {
        return Select::make($name)
            ->label($label)
            ->translateLabel()
            ->multiple()
            ->searchable()
            ->preload()
            ->live()
            ->options($options);
    }

    /**
     * @return Collection<int, Inventory>
     */
    protected static function matchingInventories(Get $get): Collection
    {
        return app(ChecklistPlanningService::class)->matchingInventories(self::scopeId(), [
            'tag_ids' => $get('tag_ids') ?? [],
            'product_group_ids' => $get('product_group_ids') ?? [],
            'product_type_ids' => $get('product_type_ids') ?? [],
            'product_model_ids' => $get('product_model_ids') ?? [],
            'location_ids' => $get('location_ids') ?? [],
            'inventory_ids' => $get('inventory_ids') ?? [],
        ]);
    }

    /**
     * Users of the current scope.
     *
     * @return array<int, string>
     */
    protected static function technicianOptions(): array
    {
        return User::query()
            ->whereHas('scopes', fn (Builder $scopes): Builder => $scopes->whereKey(self::scopeId()))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
