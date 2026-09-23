<?php

namespace App\Models;

use App\Enums\ChecklistOutcome;
use App\Enums\ChecklistResponseType;
use BackedEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChecklistTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_template_id',
        'sort',
        'label',
        'help',
        'response_type',
        'unit',
        'options',
        'min',
        'max',
        'is_required',
    ];

    protected $casts = [
        'response_type' => ChecklistResponseType::class,
        'options' => 'array',
        'min' => 'float',
        'max' => 'float',
        'is_required' => 'boolean',
    ];

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function checklist_template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Items without tags apply to every inventory, the others only to the
     * inventories having at least one of their tags.
     *
     * @param  list<int>  $inventoryTagIds
     */
    public function appliesToTags(array $inventoryTagIds): bool
    {
        $itemTagIds = $this->tags->modelKeys();

        return $itemTagIds === [] || array_intersect($itemTagIds, $inventoryTagIds) !== [];
    }

    /**
     * Whether the answer reveals an anomaly: a KO outcome or a number out of
     * the min/max range.
     */
    public function isAnomaly(mixed $value): bool
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (blank($value)) {
            return false;
        }

        return match ($this->response_type) {
            ChecklistResponseType::Outcome => $value === ChecklistOutcome::Ko->value,
            ChecklistResponseType::Number => is_numeric($value)
                && (($this->min !== null && (float) $value < $this->min) || ($this->max !== null && (float) $value > $this->max)),
            default => false,
        };
    }
}
