<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Traits\CancelToCloseAction;
use App\Traits\EditFirstRedirectUrlOverride;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStock extends CreateRecord
{
    use CancelToCloseAction, EditFirstRedirectUrlOverride;
    protected static string $resource = StockResource::class;
}
