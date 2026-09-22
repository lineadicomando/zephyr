<?php

namespace App\Traits;

use Filament\Facades\Filament;

trait PreventRelatedDeletion
{
    public ?string $failureState = null;

    public function preventDeletionBy()
    {
        return [];
    }

    /**
     * Determine whether the record is referenced by any relation listed in
     * preventDeletionBy(). The tenancy scope is ignored so that records used
     * by other tenants are detected as well.
     */
    public function hasRelated(): bool
    {
        foreach ($this->preventDeletionBy() as $method) {
            if ($this->$method()->withoutGlobalScope(Filament::getTenancyScopeName())->exists()) {
                return true;
            }
        }

        return false;
    }

    public function delete()
    {
        if ($this->hasRelated()) {
            $this->failureState = __('The record cannot be deleted because it is linked to another.');

            return false;
        }

        return parent::delete();
    }
}
