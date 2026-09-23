<?php

namespace App\Support\Export;

use Illuminate\Support\Str;

final class ExportFilename
{
    public static function forCurrentScope(string $resourceLabel): string
    {
        return implode('_', [
            self::normalizeSegment(now()->format('Y-m-d')),
            self::normalizeSegment(self::activeScopeName()),
            self::normalizeSegment($resourceLabel),
        ]);
    }

    private static function activeScopeName(): string
    {
        $scopeName = filament()->getTenant()?->getAttribute('name');

        if (! is_string($scopeName) || $scopeName === '') {
            return 'no-scope';
        }

        return $scopeName;
    }

    private static function normalizeSegment(string $value): string
    {
        $normalized = Str::slug($value, '-');

        return $normalized !== '' ? $normalized : 'na';
    }
}
