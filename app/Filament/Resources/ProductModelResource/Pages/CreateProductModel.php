<?php

namespace App\Filament\Resources\ProductModelResource\Pages;

use App\Filament\Resources\ProductModelResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductModel extends CreateRecord
{
    use CancelToCloseAction;
    protected static string $resource = ProductModelResource::class;
}
