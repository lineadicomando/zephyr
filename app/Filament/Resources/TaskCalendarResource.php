<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\CalendarTask;

class TaskCalendarResource extends TaskResource
{

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    // Nav top bar sorting
    protected static ?int $navigationSort = 4;

    public static function getGlobalSearchResults(string $search): \Illuminate\Support\Collection
    {
        return new \Illuminate\Support\Collection();
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getModelLabel(): string
    {
        return (__('Calendar'));
    }

    public static function getPluralModelLabel(): string
    {
        return (__('Calendar'));
    }

    public static function getPages(): array
    {
        return [
            'index' => CalendarTask::route('/'),
        ];
    }
}
