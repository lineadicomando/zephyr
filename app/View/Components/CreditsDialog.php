<?php

namespace App\View\Components;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class CreditsDialog
{
    public static function resolveDatabaseVersion(): string
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $pdo = $connection->getPdo();
            $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

            if (is_string($serverVersion) && $serverVersion !== '') {
                return sprintf('%s %s', strtoupper($driver), $serverVersion);
            }

            return strtoupper($driver);
        } catch (Throwable) {
            return 'unknown';
        }
    }
}
