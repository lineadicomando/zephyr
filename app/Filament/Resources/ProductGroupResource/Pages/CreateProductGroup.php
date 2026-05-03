<?php

namespace App\Filament\Resources\ProductGroupResource\Pages;

use App\Filament\Resources\ProductGroupResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductGroup extends CreateRecord
{
    use CancelToCloseAction;
    protected static string $resource = ProductGroupResource::class;
}
