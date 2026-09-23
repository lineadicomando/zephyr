<?php

namespace App\Filament\Tables\Filters;

use Closure;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Location, position and product filters of the inventory and stock tables.
 * The position filter lists only the positions of the selected location and
 * the model filter only the models of the selected brand.
 */
class InventoryFilters
{
    /**
     * @param  string  $stockRelation  relation to the stocks, '' when filtering stocks
     * @param  string  $productRelation  relation to the product columns, '' when the record has them
     * @return list<SelectFilter>
     */
    public static function make(string $stockRelation = '', string $productRelation = ''): array
    {
        $stockPrefix = $stockRelation === '' ? '' : "{$stockRelation}.";
        $productPrefix = $productRelation === '' ? '' : "{$productRelation}.";

        $locationFilter = self::select('location', 'Location', "{$stockPrefix}inventory_location", 'name');
        $brandFilter = self::select('product_brand', 'Brand', "{$productPrefix}product_brand", 'name');

        return [
            $locationFilter,
            self::select('position', 'Position', "{$stockPrefix}inventory_position", 'path', self::limitedTo($locationFilter, 'inventory_location_id')),
            self::select('product_group', 'Group', "{$productPrefix}product_group", 'name'),
            self::select('product_type', 'Type', "{$productPrefix}product_type", 'name'),
            $brandFilter,
            self::select('product_model', 'Model', "{$productPrefix}product_model", 'name', self::limitedTo($brandFilter, 'product_brand_id')),
        ];
    }

    private static function select(string $name, string $label, string $relationship, string $titleAttribute, ?Closure $modifyQueryUsing = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->label($label)
            ->translateLabel()
            ->searchable()
            ->preload()
            ->relationship($relationship, $titleAttribute, $modifyQueryUsing);
    }

    /**
     * Limit the options to the records of the value selected in $parentFilter.
     */
    private static function limitedTo(SelectFilter $parentFilter, string $column): Closure
    {
        return function (Builder $query) use ($parentFilter, $column): Builder {
            $parentValue = $parentFilter->getState()['value'] ?? null;

            return filled($parentValue) ? $query->where($column, $parentValue) : $query;
        };
    }
}
