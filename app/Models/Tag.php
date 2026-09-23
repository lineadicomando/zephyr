<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'name',
        'color',
    ];

    /**
     * @return BelongsToMany<Inventory, $this>
     */
    public function inventories(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<ChecklistTemplateItem, $this>
     */
    public function checklist_template_items(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistTemplateItem::class)->withTimestamps();
    }

    /**
     * Deleting a tag used by a checklist item would silently extend the item
     * to every inventory.
     */
    public function preventDeletionBy()
    {
        return ['checklist_template_items'];
    }
}
