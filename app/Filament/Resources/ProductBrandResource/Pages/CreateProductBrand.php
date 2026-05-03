<?php

namespace App\Filament\Resources\ProductBrandResource\Pages;

use App\Filament\Resources\ProductBrandResource;
use App\Traits\CancelToCloseAction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductBrand extends CreateRecord
{
    use CancelToCloseAction;
    protected static string $resource = ProductBrandResource::class;
}
