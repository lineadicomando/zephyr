<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\TaskStatus;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }

    public function getDefaultTaskStatus()
    {
        // $taskDefaultStatus = TaskStatus::where('default', true)->first();
        // if (!$taskDefaultStatus) {
            return 'all';
        // }
        // return $taskDefaultStatus->id;
    }

    public function getDefaultActiveTab(): string | int | null
    {
        $activeTab = Session::get('tasksActiveTab');
        if (!$activeTab) {
            $activeTab = $this->getDefaultTaskStatus();
        }
        return $activeTab;
    }

    public function getTabs(): array
    {
        $taskStatuses = TaskStatus::all()->toArray();
        $tabs =  [];
        $tabs['all'] = Tab::make(strtoupper(__('All')))->modifyQueryUsing(function (Builder $query) {
            Session::put('tasksActiveTab', 'all');
            return $query;
        });
        foreach ($taskStatuses as $taskStatus) {
            $name = $taskStatus['name'];
            $id = $taskStatus['id'];
            $tabs[$id]  = Tab::make($name)->modifyQueryUsing(function (Builder $query) use ($id) {
                Session::put('tasksActiveTab', $id);
                return $query->where('task_status_id', $id);
            });
        }
        return $tabs;
    }
}
