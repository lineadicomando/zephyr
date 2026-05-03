<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Resources\Pages\Page;

class CalendarTask extends Page
{
    // protected static ?string $title = 'Custom Page Title';

    protected static string $resource = TaskResource::class;

    protected string $view = 'filament.resources.task-resource.pages.calendar';

    public function getTitle(): string
    {
        return __('Calendar');
    }

    public function mount(): void
    {
        static::authorizeResourceAccess();
    }

}
