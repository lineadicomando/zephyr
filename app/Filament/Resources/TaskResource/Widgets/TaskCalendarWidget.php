<?php

namespace App\Filament\Resources\TaskResource\Widgets;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TaskCalendarWidget extends FullCalendarWidget
{

    public static function boot()
    {
        view()->composer('*', function ($view) {
            $panel = Filament::getCurrentPanel();

            if (($panel?->getId() !== 'app') || (!$panel->hasPlugin('filament-fullcalendar'))) {
                return;
            }

            $plugin = \Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::get();
            $plugin->editable(auth()->user()?->can('update', Task::class) ?? false);
            $view->with('plugin', $plugin);
        });
    }
    public Model | string | null $model = Task::class;

    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $startDelta, array $endDelta): bool
    {
        if ($this->getModel()) {
            $this->record = $this->resolveRecord($event['id']);
        }

        try {
            $updateArray = ['starts_at' => Carbon::parse($event['start'])];
            if (!empty($event['end'])) {
                $updateArray['ends_at'] = Carbon::parse($event['end']);
            }
            $this->record->update($updateArray);

            Notification::make()
                ->title(__('Saved'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Unexpected error'))
                ->danger()
                ->send();
        }

        return false;
    }

    public function onEventDrop(
        array $event,
        array $oldEvent,
        array $relatedEvents,
        array $delta,
        ?array $oldResource,
        ?array $newResource
    ): bool
    {
        if ($this->getModel()) {
            $this->record = $this->resolveRecord($event['id']);
        }
        try {
            $updateArray = ['starts_at' => Carbon::parse($event['start'])];
            if (!empty($event['end'])) {
                $updateArray['ends_at'] = Carbon::parse($event['end']);
            }
            $this->record->update($updateArray);

            Notification::make()
                ->title(__('Saved'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Unexpected error'))
                ->danger()
                ->send();
        }
        return false;
    }

    public function onEventClick(array $event): void
    {
        if ($this->getModel()) {
            $this->record = $this->resolveRecord($event['id']);
        }

        if (!auth()->user()->can('update', $this->record)) {
            return;
        }

        $this->mountAction('edit', [
            'type' => 'edit',
            'event' => $event,
        ]);
    }

    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->hidden(!auth()->user()->can('create', Task::class))
                ->mountUsing(
                    function (\Filament\Schemas\Schema $form, array $arguments) {
                        $form->fill([
                            'starts_at' => $this->toCalendarTimezone($arguments['start'] ?? null),
                            'ends_at' => $this->toCalendarTimezone($arguments['end'] ?? null),
                        ]);
                    }
                )
        ];
    }

    protected function toCalendarTimezone(?string $dateTime): ?string
    {
        if (blank($dateTime)) {
            return null;
        }

        $timezone = config('app.calendar_timezone')
            ?: FilamentFullCalendarPlugin::make()->getTimezone();

        $hasTimezoneDesignator = (bool) preg_match('/(Z|[+\-]\d{2}:\d{2})$/', $dateTime);

        if ($hasTimezoneDesignator) {
            return Carbon::parse($dateTime)
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s');
        }

        // If calendar sends local wall time (no offset), keep it as-is in calendar timezone.
        return Carbon::parse($dateTime, $timezone)->format('Y-m-d H:i:s');
    }

    public function getFormSchema(): array
    {
        return TaskResource::getFormDefinition(modal: true);
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $tasks = Task::query()->with('task_type');
        if (!auth()->user()->isAdmin()) {
            $tasks->where('user_id', auth()->user()->id);
        }

        $tasks->where('starts_at', '<=', $fetchInfo['end']);
        $tasks->where(function ($query) use ($fetchInfo) {
            $query->where('ends_at', '>=', $fetchInfo['start'])
                ->orWhereNull('ends_at');
        });

        $events = $tasks->get()->map(
            fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->description,
                'start' => $task->starts_at,
                'allDay' => $task->all_day,
                'end' => $task->ends_at,
                'backgroundColor' => $task->task_type->chart_color,
                // 'url' => TaskResource::getUrl(name: 'edit', parameters: ['record' => $task]),
                'shouldOpenUrlInNewTab' => false
            ]
        )->all();

        return $events;
    }
}
