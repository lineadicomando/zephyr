<?php

namespace App\Filament\Actions;

use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Models\Task;
use App\Models\User;
use App\Services\Checklists\ChecklistPlanningService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

/**
 * New task with the type, checklist template and inventories of the task,
 * for the periodic interventions.
 */
class RescheduleTaskAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reschedule';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Reschedule'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->modalHeading(fn (Task $record): string => __('Reschedule').': '.$record->description)
            ->modalDescription(__('A new task with the same type, checklist template and inventories.'))
            ->modalSubmitActionLabel(__('Create task'))
            ->visible(fn (Task $record): bool => auth()->user()->can('create', Task::class) && auth()->user()->can('view', $record))
            ->authorize(fn (Task $record): bool => auth()->user()->can('create', Task::class) && auth()->user()->can('view', $record))
            ->fillForm(fn (Task $record): array => [
                'description' => $record->description,
                'user_id' => $record->user_id,
            ])
            ->schema([
                DateTimePicker::make('starts_at')
                    ->translateLabel()
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->translateLabel()
                    ->seconds(false)
                    ->afterOrEqual('starts_at'),
                Select::make('user_id')
                    ->label('User')
                    ->translateLabel()
                    ->options(fn (Task $record): array => User::query()
                        ->whereHas('scopes', fn (Builder $scopes): Builder => $scopes->whereKey($record->scope_id))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->disabled(fn (): bool => ! auth()->user()->isAdmin())
                    ->required(),
                TextInput::make('description')
                    ->translateLabel()
                    ->required(),
            ])
            ->action(function (array $data, Task $record): void {
                // Only the admins assign the tasks to other users.
                $data['user_id'] = auth()->user()->isAdmin() ? $data['user_id'] : auth()->id();

                $copy = app(ChecklistPlanningService::class)->reschedule($record, $data);

                Notification::make()
                    ->title(__('Task rescheduled'))
                    ->success()
                    ->send();

                $this->redirect(EditTask::getUrl(['record' => $copy]));
            });
    }
}
