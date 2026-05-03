<?php

namespace App\Filament\Resources\ProductTypeResource\Pages;

use App\Filament\Resources\ProductTypeResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductType extends CreateRecord
{
    use CancelToCloseAction;
    protected static string $resource = ProductTypeResource::class;
}
