<?php

namespace App\Providers;

use App\Contracts\ScopeContext;
use App\Support\Scope\ScopeAccessResolver;
use App\Support\Scope\SessionScopeContext;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScopeContext::class, SessionScopeContext::class);
        $this->app->singleton(ScopeAccessResolver::class, ScopeAccessResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch->slideOver();
            $panelSwitch
                ->simple()
                ->labels([
                    "app" => __("Home"),
                    "admin" => __("Settings"),
                ])
                ->icons(
                    [
                        // 'app' => 'heroicon-o-star',
                        "app" => "heroicon-o-home",
                        "admin" => "heroicon-o-cog-6-tooth",
                    ],
                    $asImage = false,
                )
                ->renderHook("panels::global-search.after");
        });
    }
}
