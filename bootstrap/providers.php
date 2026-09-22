<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\Filament\AppPanelProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    AppPanelProvider::class,
];
