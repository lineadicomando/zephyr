<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How the planned inventories are split into tasks.
 */
enum ChecklistGrouping: string implements HasLabel
{
    case Location = 'location';
    case Position = 'position';
    case Single = 'single';

    public function getLabel(): string
    {
        return match ($this) {
            self::Location => __('One task per location'),
            self::Position => __('One task per position'),
            self::Single => __('A single task'),
        };
    }
}
