<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Kind of answer expected by a checklist item.
 */
enum ChecklistResponseType: string implements HasLabel
{
    case Outcome = 'outcome';
    case Number = 'number';
    case Text = 'text';
    case Choice = 'choice';

    public function getLabel(): string
    {
        return match ($this) {
            self::Outcome => __('Outcome (OK / KO / N.A.)'),
            self::Number => __('Number'),
            self::Text => __('Text'),
            self::Choice => __('Choice'),
        };
    }
}
