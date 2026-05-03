<?php

namespace App\Filament\Widgets;

use App\Contracts\ScopeContext;
use App\Models\Scope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrentScopeWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getColumns(): int
    {
        return 1;
    }

    protected function getStats(): array
    {
        $activeScopeId = app(ScopeContext::class)->activeScopeId();
        $scope = $activeScopeId
            ? Scope::query()->find($activeScopeId)
            : null;

        if (! $scope) {
            return [
                Stat::make(__('Current scope'), __('Not set'))
                    ->description(__('No active scope selected in this session.'))
                    ->color('warning')
                    ->descriptionIcon('heroicon-o-exclamation-triangle'),
            ];
        }

        return [
            Stat::make(__('Current scope'), $scope->name)
                ->description(__('Type: :type | Slug: :slug', [
                    'type' => $scope->type,
                    'slug' => $scope->slug,
                ]))
                ->color($scope->is_active ? 'success' : 'danger')
                ->descriptionIcon($scope->is_active ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
        ];
    }
}
