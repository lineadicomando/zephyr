<?php

namespace App\Filament\Forms\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

class BarcodeScannerInput extends TextInput
{
    protected string $view = 'filament.forms.components.barcode-scanner-input';

    protected function setUp(): void
    {
        parent::setUp();

        $this->suffixAction(
            Action::make('scanBarcode')
                ->label(__('Scan barcode'))
                ->tooltip(__('Scan barcode'))
                ->icon(Heroicon::OutlinedCamera)
                ->disabled(fn (): bool => $this->isDisabled())
                ->alpineClickHandler(fn (): string => '$dispatch(\'open-modal\', { id: '.Js::from($this->getModalId()).' })'),
        );
    }

    public function getModalId(): string
    {
        return 'barcode-scanner-'.str_replace('.', '-', $this->getStatePath());
    }
}
