<?php

namespace App\Support\Export;

use App\Contracts\ScopeContext;
use App\Models\Scope;
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
        $scopeId = app(ScopeContext::class)->activeScopeId();

        if (! is_int($scopeId)) {
            return 'no-scope';
        }

        $scopeName = Scope::query()->whereKey($scopeId)->value('name');

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

