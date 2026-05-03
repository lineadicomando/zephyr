<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Scope\ScopePurgeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurgePendingScopesCommand extends Command
{
    protected $signature = 'scopes:purge-pending';

    protected $description = 'Purge scopes whose pending_delete timestamp has expired.';

    public function handle(): int
    {
        $now = now();
        $candidateIds = DB::table('scopes')
            ->whereNotNull('pending_delete')
            ->where('pending_delete', '<=', $now)
            ->pluck('id');

        $purgedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($candidateIds as $scopeId) {
            try {
                $wasPurged = DB::transaction(function () use ($scopeId, $now): bool {
                    $scope = DB::table('scopes')
                        ->where('id', $scopeId)
                        ->lockForUpdate()
                        ->first(['id', 'is_active', 'protected', 'pending_delete']);

                    if ($scope === null) {
                        return false;
                    }

                    if ($scope->protected || $scope->is_active || blank($scope->pending_delete)) {
                        return false;
                    }

                    if (Carbon::parse($scope->pending_delete)->isFuture()) {
                        return false;
                    }

                    foreach (ScopePurgeRegistry::tables() as $table) {
                        DB::table($table)->where('scope_id', $scopeId)->delete();
                    }

                    DB::table('scope_user')->where('scope_id', $scopeId)->delete();
                    DB::table('scopes')->where('id', $scopeId)->delete();

                    return true;
                });

                if ($wasPurged) {
                    $purgedCount++;
                } else {
                    $skippedCount++;
                }
            } catch (Throwable $exception) {
                $errorCount++;

                Log::error('Failed purging pending scope.', [
                    'scope_id' => $scopeId,
                    'exception' => $exception,
                ]);

                $this->error("Failed purging scope {$scopeId}: {$exception->getMessage()}");
            }
        }

        $this->info("Pending scopes purge completed. purged={$purgedCount}, skipped={$skippedCount}, errors={$errorCount}");

        return self::SUCCESS;
    }
}

