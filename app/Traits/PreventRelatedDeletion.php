<?php

namespace App\Traits;

use Filament\Actions\Action;

trait PreventRelatedDeletion
{
    public function preventDeletionBy()
    {
        return [];
    }


    public function hasRelated()
    {
        $hasMany = $this->preventDeletionBy();
        foreach ($hasMany as $method) {
            if ($this->$method()?->exists()) {
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
