<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Answer of the checklist items of type outcome.
 */
enum ChecklistOutcome: string implements HasColor, HasLabel
{
    case Ok = 'ok';
    case Ko = 'ko';
    case NotApplicable = 'not_applicable';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ok => __('OK'),
            self::Ko => __('KO'),
            self::NotApplicable => __('N.A.'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::Ko => 'danger',
            self::NotApplicable => 'gray',
        };
    }
}
