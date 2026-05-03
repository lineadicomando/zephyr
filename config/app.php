<?php

$appVersion = "0.1.2";

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    "name" => env("APP_NAME", "Laravel"),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    "env" => env("APP_ENV", "production"),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    "debug" => (bool) env("APP_DEBUG", false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    "url" => env("APP_URL", "http://localhost"),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    "timezone" => "UTC",
    "calendar_timezone" => env("CALENDAR_TIMEZONE", "Europe/Rome"),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    "locale" => env("APP_LOCALE", env("LOCALE", "en")),

    "fallback_locale" => env(
        "APP_FALLBACK_LOCALE",
        env("FALLBACK_LOCALE", "en"),
    ),

    "faker_locale" => env("APP_FAKER_LOCALE", "en_US"),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    "cipher" => "AES-256-CBC",

    "key" => env("APP_KEY"),

    "previous_keys" => [
        ...array_filter(explode(",", (string) env("APP_PREVIOUS_KEYS", ""))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    "maintenance" => [
        "driver" => env("APP_MAINTENANCE_DRIVER", "file"),
        "store" => env("APP_MAINTENANCE_STORE", "database"),
    ],

    "ver" => $appVersion,
    "projectName" => env("APP_PROJECT_NAME", "Zephyr"),
    "diagnostics_enabled" => env("APP_DIAGNOSTICS_ENABLED", false),

    "project" => (static function () use ($appVersion): array {
        $composer = [];
        $composerPath = base_path("composer.json");

        if (is_file($composerPath)) {
            $decoded = json_decode(
                (string) file_get_contents($composerPath),
                true,
            );

            if (is_array($decoded)) {
                $composer = $decoded;
            }
        }

        $license = $composer["license"] ?? "Proprietary";

        if (is_array($license)) {
            $license = implode(", ", array_map("strval", $license));
        }

        $authorName = "Project Maintainers";
        $authorEmail = null;
        $authorsPath = base_path("AUTHORS.md");

        if (is_file($authorsPath)) {
            $lines =
                preg_split(
                    "/\r\n|\r|\n/",
                    (string) file_get_contents($authorsPath),
                ) ?:
                [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === "" || str_starts_with($line, "#")) {
                    continue;
                }

                $line = ltrim($line, "-* ");

                if ($line !== "") {
                    $authorName = $line;
                    break;
                }
            }
        }

        if (preg_match("/<([^>]+@[^>]+)>/", $authorName, $matches) === 1) {
            $authorEmail = $matches[1];
            $authorName = trim(str_replace($matches[0], "", $authorName));
        }

        return [
            "name" => env("APP_PROJECT_NAME", "Zephyr"),
            "version" => $appVersion,
            "license" => (string) $license,
            "author_name" => $authorName,
            "author_email" => $authorEmail,
        ];
    })(),
];
