<?php

namespace App\Filament\Resources\ProductBrandResource\Pages;

use App\Filament\Resources\ProductBrandResource;
use App\Traits\ViewHeaderAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductBrand extends ViewRecord
{
    use ViewHeaderAction;
    protected static string $resource = ProductBrandResource::class;
}
