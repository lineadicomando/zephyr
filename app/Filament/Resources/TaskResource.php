<?php

namespace App\Filament\Resources;

use App\Contracts\ScopeContext;
use App\Filament\Resources\TaskStatusResource;
use App\Filament\Resources\TaskTypeResource;
use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\Pages\CalendarTask;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Filament\Resources\TaskResource\RelationManagers\InventoriesRelationManager;
use App\Models\Inventory;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static string|\BackedEnum|null $navigationIcon = "heroicon-o-wrench-screwdriver";
    protected static ?string $navigationBadgeColor = "info";

    protected static ?int $navigationSort = 3;

    // protected static string|\UnitEnum|null $navigationGroup = "Tasks";

    // public static function getNavigationGroup(): ?string
    // {
    //     return __(static::$navigationGroup);
    // }

    protected static ?string $recordTitleAttribute = "description";

    public static function getModelLabel(): string
    {
        return __("Task");
    }

    public static function getPluralModelLabel(): string
    {
        return __("Tasks");
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::$navigationBadgeColor;
    }

    public static function getNavigationBadge(): ?string
    {
        static::$navigationBadgeColor = "info";
        $taskStatusTable = app(TaskStatus::class)->getTable();
        $query = static::getModel()
            ::join(
                $taskStatusTable,
                $taskStatusTable . ".id",
                "=",
                "task_status_id",
            )
            ->where("{$taskStatusTable}.default", true);

        if (!auth()->user()->isAdmin()) {
            $query->where("user_id", auth()->user()->id);
        }

        $count = $query->count();

        if ($count === 0) {
            $query = static::getModel()
                ::join(
                    $taskStatusTable,
                    $taskStatusTable . ".id",
                    "=",
                    "task_status_id",
                )
                ->where("{$taskStatusTable}.default", "<>", true)
                ->where("{$taskStatusTable}.completed", "<>", true);
            if (!auth()->user()->isAdmin()) {
                $query->where("user_id", auth()->user()->id);
            }
            $count = $query->count();
            if ($count > 0) {
                static::$navigationBadgeColor = "primary";
            }
        }

        return $count > 0 ? $count : null;
    }

    public static function getFormDefinition(bool $modal = false): array
    {
        $userIsAdmin = auth()->user()?->isAdmin();
        $formSchema = [
            Hidden::make('scope_id')
                ->default(fn (): ?int => app(ScopeContext::class)->activeScopeId())
                ->dehydrated(),
            Group::make()
                ->schema([
                    ($startsAt = DateTimePicker::make("starts_at")
                        ->default(date("Y-m-d H:i"))
                        ->seconds(false)
                        ->translateLabel()),
                    ($endsAt = DateTimePicker::make("ends_at")
                        ->seconds(false)
                        ->translateLabel()),
                ])
                ->columns(2),
            Toggle::make("all_day")
                ->live()
                ->afterStateUpdated(function (string $state, Task $task) use (
                    &$startsAt,
                    &$endsAt,
                ) {
                    if ($state) {
                        $initialDateTime =
                            $startsAt->getState() ??
                            $startsAt->getDefaultState();
                        $carbon = \Illuminate\Support\Carbon::parse(
                            $initialDateTime,
                        );
                        $initialDate = $carbon->format("Y-m-d");
                        $startsAt->state(
                            date(
                                $initialDate . " " . env("ZPH_TIME_START_WORK"),
                            ),
                        );
                        $endsAt->state(
                            date($initialDate . " " . env("ZPH_TIME_END_WORK")),
                        );
                    } else {
                        if ($task) {
                            $startsAt->state(
                                $task->exists
                                    ? $task->getOriginal("starts_at")
                                    : $startsAt->getDefaultState(),
                            );
                            $endsAt->state($task->getOriginal("ends_at"));
                        }
                    }
                })
                ->translateLabel()
                ->inline(false),
            Select::make("task_type_id")
                ->label("Type")
                ->searchable()
                ->preload()
                ->required()
                ->translateLabel()
                ->relationship("task_type", "name")
                ->createOptionForm(
                    $userIsAdmin ? TaskTypeResource::getFormDefinition() : null,
                )
                ->editOptionForm(
                    $userIsAdmin ? TaskTypeResource::getFormDefinition() : null,
                ),
            Select::make("task_status_id")
                ->label("Status")
                ->searchable()
                ->preload()
                ->required()
                ->translateLabel()
                ->default(TaskStatus::getDefaultId())
                ->relationship(
                    "task_status",
                    "name",
                    modifyQueryUsing: function (Builder $query) use (
                        $userIsAdmin,
                    ) {
                        if (!$userIsAdmin) {
                            $query->leftJoin("permission_entities", function (
                                \Illuminate\Database\Query\JoinClause $join,
                            ) {
                                $join
                                    ->on(
                                        "task_statuses.id",
                                        "=",
                                        "permission_entities.entity_id",
                                    )
                                    ->where("entity_type", TaskStatus::class);
                            });
                            $query
                                ->whereNull("permission_entities.id")
                                ->orWhere("permission_entities.view", true);
                        }
                        return $query->orderBy("order", "asc");
                    },
                )
                ->createOptionForm(
                    $userIsAdmin
                        ? TaskStatusResource::getFormDefinition()
                        : null,
                )
                ->editOptionForm(
                    $userIsAdmin
                        ? TaskStatusResource::getFormDefinition()
                        : null,
                ),
            TextInput::make("description")->required()->translateLabel(),
            Select::make("user_id")
                ->translateLabel()
                ->searchable()
                ->disabled(!auth()->user()->isAdmin())
                ->default(Auth()->user()->id)
                ->preload()
                ->relationship("user", "name"),
            Textarea::make("note")->columnSpanFull()->translateLabel(),
        ];
        if ($modal) {
            $formSchema[] = Select::make("inventories")
                ->translateLabel()
                ->multiple()
                ->searchable()
                ->relationship("inventories", "summary")
                ->columnSpanFull();
        }
        return $formSchema;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(self::getFormDefinition());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (!auth()->user()->isAdmin()) {
                    $query->where("user_id", auth()->user()->id);
                }
                return $query;
            })
            ->columns([
                TextColumn::make("id")
                    ->label("#")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("starts_at")
                    ->date(env("DATETIME_FORMAT"))
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make("ends_at")
                    ->date(env("DATETIME_FORMAT"))
                    ->translateLabel()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("task_status.name")
                    ->badge()
                    ->color(
                        fn(string $state, Task $task) => $task->task_status
                            ->color,
                    )
                    ->icon(
                        fn(string $state, Task $task) => $task->task_status
                            ->icon,
                    )
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make("task_type.name")
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make("inventories.summary")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label("Inventory")
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->wrap()
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make("description")
                    ->wrap()
                    ->translateLabel()
                    ->searchable(isGlobal: true)
                    ->sortable(),
                TextColumn::make("created_at")
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("updated_at")
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('user.name')
                //     ->wrap()
                //     ->translateLabel()
                //     ->searchable()
                //     ->sortable(),
            ])
            ->defaultSort("created_at", "desc")
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->filters([
                SelectFilter::make("task_type")
                    ->translateLabel()
                    ->relationship("task_type", "name"),
            ])
            ->persistFiltersInSession()
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can("update", Task::class)) {
                    return Pages\EditTask::getUrl([$record->id]);
                }
                return Pages\ViewTask::getUrl([$record->id]);
            })
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [InventoriesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListTasks::route("/"),
            "create" => Pages\CreateTask::route("/create"),
            "edit" => Pages\EditTask::route("/{record}/edit"),
            "view" => Pages\ViewTask::route("/{record}/view"),
            // 'calendar' => CalendarTask::route('/calendar'),
        ];
    }
}
