<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ZephyrSetup extends Command
{
    protected $signature = "zephyr:setup {--force : Overwrite .env without confirmation} {--no-fresh : Use migrate --seed instead of migrate:fresh --seed}";

    protected $description = "Interactive setup for Zephyr: generate .env from .env.example and run migrations/seeding";

    public function handle(): int
    {
        $envExamplePath = base_path(".env.example");
        $envPath = base_path(".env");

        if (!File::exists($envExamplePath)) {
            $this->error(".env.example not found.");

            return self::FAILURE;
        }

        if (File::exists($envPath) && !$this->option("force")) {
            if (
                !$this->confirm(
                    ".env already exists. Do you want to overwrite it?",
                    false,
                )
            ) {
                $this->warn("Setup aborted.");

                return self::INVALID;
            }
        }

        $template = File::get($envExamplePath);
        $defaults = $this->parseEnv($template);

        $this->info("Zephyr interactive setup");

        $values = $this->collectValues($defaults);
        $effectiveEnv = array_merge($defaults, $values);

        if (!$this->canConnectToDatabase($values)) {
            $this->error(
                "Database connection failed with the provided settings.",
            );

            if (!$this->confirm("Do you want to continue anyway?", false)) {
                $this->warn("Setup aborted.");

                return self::FAILURE;
            }
        }

        $envContent = $this->applyValuesToTemplate($template, $values);
        File::put($envPath, $envContent);

        $this->info(".env generated successfully.");

        $this->applyRuntimeEnvironment($effectiveEnv);
        $this->syncRuntimeConfig($effectiveEnv);

        // Avoid conflicts when APP_KEY is injected at process-level env.
        putenv("APP_KEY");
        unset($_ENV["APP_KEY"], $_SERVER["APP_KEY"]);
        config()->set("app.key", null);

        $keyExit = $this->call("key:generate", ["--force" => true]);
        if ($keyExit !== self::SUCCESS) {
            $this->error("key:generate failed.");

            return self::FAILURE;
        }

        $seedCommand =
            ($effectiveEnv["SEED_DEMO_DATA"] ?? "false") === "true"
                ? "migrate:seed_demo"
                : "migrate:seed";

        $seedExit = $this->call($seedCommand, [
            "--no-fresh" => (bool) $this->option("no-fresh"),
        ]);

        if ($seedExit !== self::SUCCESS) {
            $this->error($seedCommand . " failed.");

            return self::FAILURE;
        }

        $this->clearBootstrapAdminPasswordFromEnv($envPath);

        $this->info("Zephyr setup completed successfully.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $defaults
     * @return array<string, string>
     */
    private function collectValues(array $defaults): array
    {
        $appLocale = select(
            label: "Application locale",
            options: [
                "en" => "English (en)",
                "it" => "Italiano (it)",
            ],
            default: $defaults["APP_LOCALE"] ?? "en",
        );

        $appUrl = text(
            label: "Application URL",
            default: $defaults["APP_URL"] ?? "http://localhost:8000",
            required: true,
        );

        $appDebug = confirm(
            label: "Enable debug mode (APP_DEBUG)?",
            default: false,
        );

        $timezone = select(
            label: "Calendar timezone",
            options: [
                "UTC" => "UTC",
                "Europe/Rome" => "Europe/Rome",
                "Europe/London" => "Europe/London",
                "America/New_York" => "America/New_York",
                "America/Los_Angeles" => "America/Los_Angeles",
                "Asia/Tokyo" => "Asia/Tokyo",
                "__custom__" => "Custom...",
            ],
            default: $defaults["CALENDAR_TIMEZONE"] ?? "UTC",
        );

        if ($timezone === "__custom__") {
            $timezone = text(
                label: "Custom calendar timezone",
                default: $defaults["CALENDAR_TIMEZONE"] ?? "UTC",
                required: true,
            );
        }

        $dateFormat = select(
            label: "Date format",
            options: [
                "j M Y" => "Italian style: 5 Mag 2026 (j M Y)",
                "d/m/Y" => "European numeric: 05/05/2026 (d/m/Y)",
                "d-m-Y" => "European numeric with dash: 05-05-2026 (d-m-Y)",
                "m/d/Y" => "US numeric: 05/05/2026 (m/d/Y)",
                "Y-m-d" => "ISO date: 2026-05-05 (Y-m-d)",
            ],
            default: $defaults["DATE_FORMAT"] ?? "j M Y",
        );

        $dateTimeFormat = select(
            label: "Datetime format",
            options: [
                "j M Y H:i" => "Italian style: 5 Mag 2026 14:30 (j M Y H:i)",
                "d/m/Y H:i" => "European numeric: 05/05/2026 14:30 (d/m/Y H:i)",
                "d-m-Y H:i" =>
                    "European numeric with dash: 05-05-2026 14:30 (d-m-Y H:i)",
                "m/d/Y h:i A" => "US 12h: 05/05/2026 02:30 PM (m/d/Y h:i A)",
                "Y-m-d H:i" => "ISO-like: 2026-05-05 14:30 (Y-m-d H:i)",
            ],
            default: $defaults["DATETIME_FORMAT"] ?? "j M Y H:i",
        );

        $startWork = text(
            label: "Workday start time (HH:MM)",
            default: $defaults["ZPH_TIME_START_WORK"] ?? "07:30",
            required: true,
            validate: fn(string $value) => preg_match(
                '/^([01]\d|2[0-3]):[0-5]\d$/',
                $value,
            )
                ? null
                : "Use 24h format HH:MM (example: 07:30)",
        );

        $endWork = text(
            label: "Workday end time (HH:MM)",
            default: $defaults["ZPH_TIME_END_WORK"] ?? "13:30",
            required: true,
            validate: fn(string $value) => preg_match(
                '/^([01]\d|2[0-3]):[0-5]\d$/',
                $value,
            )
                ? null
                : "Use 24h format HH:MM (example: 13:30)",
        );

        $defaultWorkDaysNumeric = array_values(
            array_filter(
                array_map(
                    static fn(string $day): string => trim($day),
                    explode(",", $defaults["ZPH_WORK_DAYS"] ?? "1,2,3,4,5,6"),
                ),
            ),
        );

        $dayTokenToNumeric = [
            "mon" => "1",
            "tue" => "2",
            "wed" => "3",
            "thu" => "4",
            "fri" => "5",
            "sat" => "6",
            "sun" => "7",
        ];

        $numericToDayToken = array_flip($dayTokenToNumeric);
        $defaultWorkDays = array_values(
            array_filter(
                array_map(
                    static fn(string $n): ?string => $numericToDayToken[$n] ??
                        null,
                    $defaultWorkDaysNumeric,
                ),
            ),
        );

        $useDefaultWorkDays = confirm(
            label: "Use default working days (Mon-Sat)?",
            default: $defaultWorkDaysNumeric === ["1", "2", "3", "4", "5", "6"],
        );

        $workDays = multiselect(
            label: "Working days",
            options: [
                "mon" => "Monday",
                "tue" => "Tuesday",
                "wed" => "Wednesday",
                "thu" => "Thursday",
                "fri" => "Friday",
                "sat" => "Saturday",
                "sun" => "Sunday",
            ],
            default: $useDefaultWorkDays
                ? ["mon", "tue", "wed", "thu", "fri", "sat"]
                : $defaultWorkDays,
            required: true,
        );

        $seedDemoData = confirm(
            label: "Load demo data?",
            default: filter_var(
                $defaults["SEED_DEMO_DATA"] ?? "false",
                FILTER_VALIDATE_BOOL,
            ),
        );

        return [
            "DB_CONNECTION" => text(
                label: "Database driver",
                default: $defaults["DB_CONNECTION"] ?? "mariadb",
                required: true,
            ),
            "DB_HOST" => text(
                label: "Database host",
                default: $defaults["DB_HOST"] ?? "127.0.0.1",
                required: true,
            ),
            "DB_PORT" => text(
                label: "Database port",
                default: $defaults["DB_PORT"] ?? "3306",
                required: true,
            ),
            "DB_DATABASE" => text(
                label: "Database name",
                default: $defaults["DB_DATABASE"] ?? "zephyr",
                required: true,
            ),
            "DB_USERNAME" => text(
                label: "Database username",
                default: $defaults["DB_USERNAME"] ?? "root",
                required: true,
            ),
            "DB_PASSWORD" => $this->promptPasswordWithConfirmation(
                label: "Database password",
                fallback: $defaults["DB_PASSWORD"] ?? "",
                allowEmpty: true,
            ),

            "APP_LOCALE" => $appLocale,
            "APP_URL" => $appUrl,
            "APP_DEBUG" => $appDebug ? "true" : "false",
            "LOCALE" => $appLocale,
            "APP_FALLBACK_LOCALE" => $defaults["APP_FALLBACK_LOCALE"] ?? "en",
            "FALLBACK_LOCALE" => "en",
            "CALENDAR_TIMEZONE" => $timezone,
            "DATE_FORMAT" => $dateFormat,
            "DATETIME_FORMAT" => $dateTimeFormat,
            "ZPH_TIME_START_WORK" => $startWork,
            "ZPH_TIME_END_WORK" => $endWork,
            "ZPH_WORK_DAYS" => implode(
                ",",
                array_values(
                    array_map(
                        static fn(string $token): string => $dayTokenToNumeric[
                            $token
                        ] ?? $token,
                        $workDays,
                    ),
                ),
            ),

            "BOOTSTRAP_ADMIN_NAME" => text(
                label: "Bootstrap admin name",
                default: $defaults["BOOTSTRAP_ADMIN_NAME"] ?? "Admin",
                required: true,
            ),
            "BOOTSTRAP_ADMIN_EMAIL" => text(
                label: "Bootstrap admin email",
                default: $defaults["BOOTSTRAP_ADMIN_EMAIL"] ??
                    "admin@example.com",
                required: true,
            ),
            "BOOTSTRAP_ADMIN_PASSWORD" => $this->promptPasswordWithConfirmation(
                label: "Bootstrap admin password",
                fallback: $defaults["BOOTSTRAP_ADMIN_PASSWORD"] ?? "",
                allowEmpty: false,
            ),
            "SEED_DEMO_DATA" => $seedDemoData ? "true" : "false",
        ];
    }

    /**
     * @param  array<string, string>  $values
     */
    private function canConnectToDatabase(array $values): bool
    {
        $driver = $values["DB_CONNECTION"];
        config()->set("database.default", $driver);

        if ($driver === "sqlite") {
            config()->set("database.connections.sqlite", [
                "driver" => "sqlite",
                "url" => null,
                "database" => $values["DB_DATABASE"],
                "prefix" => "",
                "foreign_key_constraints" => true,
            ]);
        } else {
            config()->set("database.connections." . $driver, [
                "driver" => $driver,
                "host" => $values["DB_HOST"],
                "port" => (int) $values["DB_PORT"],
                "database" => $values["DB_DATABASE"],
                "username" => $values["DB_USERNAME"],
                "password" => $values["DB_PASSWORD"],
                "unix_socket" => "",
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_unicode_ci",
                "prefix" => "",
                "prefix_indexes" => true,
                "strict" => true,
                "engine" => null,
            ]);
        }

        try {
            DB::purge($driver);
            DB::connection($driver)->getPdo();

            $this->info("Database connection successful.");

            return true;
        } catch (Throwable $e) {
            $this->warn("DB error: " . $e->getMessage());

            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseEnv(string $content): array
    {
        $result = [];

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim($line);

            if (
                $line === "" ||
                str_starts_with($line, "#") ||
                !str_contains($line, "=")
            ) {
                continue;
            }

            [$key, $value] = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value);
            $result[$key] = trim($value, "\"'");
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function applyValuesToTemplate(
        string $template,
        array $values,
    ): string {
        $output = $template;

        foreach ($values as $key => $value) {
            $escaped = $this->escapeEnvValue($value);
            $pattern = "/^" . preg_quote($key, "/") . '=.*$/m';

            if (preg_match($pattern, $output) === 1) {
                $output =
                    preg_replace($pattern, $key . "=" . $escaped, $output) ??
                    $output;
            } else {
                $output .= PHP_EOL . $key . "=" . $escaped;
            }
        }

        return $output;
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === "") {
            return "";
        }

        if (strpbrk($value, " \t\n\r\0\x0B#\"'") !== false) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        return $value;
    }

    private function promptPasswordWithConfirmation(
        string $label,
        string $fallback = "",
        bool $allowEmpty = false,
    ): string {
        while (true) {
            $first = password(label: "{$label} (hidden input)");
            $second = password(label: "{$label} confirmation");

            if ($first === "" && $allowEmpty) {
                return $fallback;
            }

            if ($first === "") {
                $this->error("Password cannot be empty.");

                continue;
            }

            if ($first !== $second) {
                $this->error(
                    "Password confirmation does not match. Please try again.",
                );

                continue;
            }

            return $first;
        }
    }

    /**
     * @param  array<string, string>  $effectiveEnv
     */
    private function applyRuntimeEnvironment(array $effectiveEnv): void
    {
        foreach ($effectiveEnv as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    /**
     * @param  array<string, string>  $effectiveEnv
     */
    private function syncRuntimeConfig(array $effectiveEnv): void
    {
        if (isset($effectiveEnv["DB_CONNECTION"])) {
            config()->set("database.default", $effectiveEnv["DB_CONNECTION"]);
        }

        if (isset($effectiveEnv["CACHE_STORE"])) {
            config()->set("cache.default", $effectiveEnv["CACHE_STORE"]);
        }

        if (($effectiveEnv["CACHE_STORE"] ?? null) === "database") {
            config()->set(
                "cache.stores.database.connection",
                $effectiveEnv["DB_CACHE_CONNECTION"] ??
                    ($effectiveEnv["DB_CONNECTION"] ??
                        config("database.default")),
            );
        }
    }

    private function clearBootstrapAdminPasswordFromEnv(string $envPath): void
    {
        if (!File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);
        $updated = $this->applyValuesToTemplate($content, [
            "BOOTSTRAP_ADMIN_PASSWORD" => "",
        ]);
        File::put($envPath, $updated);

        putenv("BOOTSTRAP_ADMIN_PASSWORD=");
        $_ENV["BOOTSTRAP_ADMIN_PASSWORD"] = "";
        $_SERVER["BOOTSTRAP_ADMIN_PASSWORD"] = "";
    }
}
