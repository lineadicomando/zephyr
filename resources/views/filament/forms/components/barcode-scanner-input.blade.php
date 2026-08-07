@include('filament-forms::components.text-input')

@php
    $statePath = $getStatePath();
    $modalId = $field->getModalId();
@endphp

<x-filament::modal
    :id="$modalId"
    :heading="__('Scan barcode / QR code')"
    width="sm"
>
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('barcode-scanner', package: 'app') }}"
        x-data="barcodeScanner({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            modalId: @js($modalId),
            cameraErrorMessage: @js(__('Camera access denied or unavailable.')),
        })"
        x-on:x-modal-opened.document="if ($event.detail.id === modalId) startScanner()"
        x-on:modal-closed.window="if ($event.detail.id === modalId) stopScanner()"
    >
        <div id="{{ $modalId }}-viewport" style="min-height: 280px;"></div>

        <div x-show="errorMessage" x-cloak>
            <x-filament::callout color="danger">
                <x-slot name="description">
                    <span x-text="errorMessage"></span>
                </x-slot>
            </x-filament::callout>
        </div>
    </div>

    <x-slot name="footer">
        <x-filament::button
            color="gray"
            x-on:click="$dispatch('close-modal', { id: '{{ $modalId }}' })"
        >
            {{ __('Cancel') }}
        </x-filament::button>
    </x-slot>
</x-filament::modal>
