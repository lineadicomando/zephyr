<?php

namespace App\Traits;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait HasDbCheck
{
    public static function info(string $msg): void
    {
        info($msg);
    }

    public static function error(string $msg): void
    {
        error($msg);
    }

    public static function warning(string $msg): void
    {
        warning($msg);
    }

    public static function dbCheck(bool $output = false): void
    {
        self::warning('dbCheck:: To do');
    }
}
