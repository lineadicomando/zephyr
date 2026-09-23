<?php

namespace App\Filament\Forms;

use App\Enums\ChecklistOutcome;
use App\Enums\ChecklistResponseType;
use App\Models\ChecklistResult;
use App\Models\ChecklistTemplateItem;
use App\Models\TaskInventory;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;

/**
 * Form to fill the checklist of an inventory of a task, built from the
 * applicable template items. The answer of the item with id N is stored
 * in the state under "items.item_N".
 */
class ChecklistForm
{
    /**
     * Disk of the photos: private, the files are served by signed temporary URLs.
     */
    public const PHOTOS_DISK = 'local';

    public const PHOTOS_DIRECTORY = 'checklists';

    /**
     * @param  Collection<int, ChecklistTemplateItem>  $items
     * @return list<Section>
     */
    public static function schema(Collection $items): array
    {
        $sections = $items
            ->map(fn (ChecklistTemplateItem $item): Section => Section::make($item->label.($item->is_required ? ' *' : ''))
                ->description($item->help)
                ->compact()
                ->schema([
                    self::valueField($item),
                    TextInput::make(self::statePath($item, 'note'))
                        ->label('Note')
                        ->translateLabel(),
                    self::photosField(self::statePath($item, 'photos'))
                        ->helperText(__('Photos of the anomaly.'))
                        ->visible(fn (Get $get): bool => $item->isAnomaly($get(self::statePath($item, 'value'))) || filled($get(self::statePath($item, 'photos')))),
                ]))
            ->values()
            ->all();

        $sections[] = Section::make(__('General notes'))
            ->compact()
            ->schema([
                Textarea::make('note')
                    ->label('Note')
                    ->translateLabel(),
                self::photosField('photos'),
            ]);

        return $sections;
    }

    /**
     * Form state of the checklist saved for the inventory.
     *
     * @return array<string, mixed>
     */
    public static function fill(TaskInventory $taskInventory): array
    {
        $items = $taskInventory->results
            ->filter(fn (ChecklistResult $result): bool => $result->checklist_template_item_id !== null)
            ->mapWithKeys(fn (ChecklistResult $result): array => ["item_{$result->checklist_template_item_id}" => [
                'value' => $result->value,
                'note' => $result->note,
                'photos' => $result->photos ?? [],
            ]])
            ->all();

        return [
            'items' => $items,
            'note' => $taskInventory->note,
            'photos' => $taskInventory->photos ?? [],
        ];
    }

    /**
     * Answers of the form state by template item id.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{value?: mixed, note?: ?string, photos?: ?array<string>}>
     */
    public static function answers(array $data): array
    {
        return collect($data['items'] ?? [])
            ->mapWithKeys(fn (array $answer, string $key): array => [(int) str_replace('item_', '', $key) => $answer])
            ->all();
    }

    private static function statePath(ChecklistTemplateItem $item, string $field): string
    {
        return "items.item_{$item->id}.{$field}";
    }

    private static function valueField(ChecklistTemplateItem $item): Field
    {
        $name = self::statePath($item, 'value');

        $field = match ($item->response_type) {
            ChecklistResponseType::Outcome => ToggleButtons::make($name)
                ->options(ChecklistOutcome::class)
                ->inline()
                ->live(),
            ChecklistResponseType::Number => TextInput::make($name)
                ->numeric()
                ->suffix($item->unit)
                ->helperText(self::rangeDescription($item))
                ->live(onBlur: true),
            ChecklistResponseType::Choice => Select::make($name)
                ->options(array_combine($item->options ?? [], $item->options ?? [])),
            ChecklistResponseType::Text => Textarea::make($name)
                ->rows(2),
        };

        return $field
            ->label($item->label)
            ->hiddenLabel()
            ->required($item->is_required);
    }

    private static function photosField(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->label('Photos')
            ->translateLabel()
            ->image()
            ->multiple()
            ->disk(self::PHOTOS_DISK)
            ->directory(self::PHOTOS_DIRECTORY)
            ->visibility('private')
            ->maxSize(10240)
            ->automaticallyResizeImagesMode('contain')
            ->automaticallyResizeImagesToWidth('1600')
            ->automaticallyResizeImagesToHeight('1600')
            ->openable();
    }

    private static function rangeDescription(ChecklistTemplateItem $item): ?string
    {
        return match (true) {
            $item->min !== null && $item->max !== null => __('Expected between :min and :max', ['min' => $item->min, 'max' => $item->max]),
            $item->min !== null => __('Expected at least :min', ['min' => $item->min]),
            $item->max !== null => __('Expected at most :max', ['max' => $item->max]),
            default => null,
        };
    }
}
