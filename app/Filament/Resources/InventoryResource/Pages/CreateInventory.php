<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Traits\CancelToCloseAction;
use App\Traits\EditFirstRedirectUrlOverride;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateInventory extends CreateRecord
{
    use CancelToCloseAction, EditFirstRedirectUrlOverride;
    protected static string $resource = InventoryResource::class;

    public function afterCreate()
    {
        if (!empty($this->record->inventory_number)) {
            return true;
        }
        $inventoryModel = $this->getModel();
        if (!$inventoryModel) {
            return false;
        }
        $inventoryRecord = $inventoryModel::find($this->record->id);
        if (!$inventoryRecord) {
            return false;
        }
        $inventoryRecord->update([
            'inventory_number' => str_pad($this->record->id, env('INVENTORY_NUMBER_ZERO_FILL', 6), '0', STR_PAD_LEFT)
        ]);
    }
}
