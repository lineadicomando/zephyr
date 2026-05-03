<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class TaskSeeder extends Seeder
{
    public function __construct(
        private readonly ?int $scopeId = null,
    ) {
    }

    public function run(): void
    {
        if (! is_int($this->scopeId)) {
            throw new RuntimeException('TaskSeeder requires a scope id.');
        }

        $types = collect([
            [
                "name" => "Maintenance",
                "chart" => true,
                "chart_color" => "#3b82f6",
            ],
            [
                "name" => "Installation",
                "chart" => true,
                "chart_color" => "#22c55e",
            ],
            [
                "name" => "Inspection",
                "chart" => false,
                "chart_color" => "#a855f7",
            ],
            ["name" => "Repair", "chart" => true, "chart_color" => "#ef4444"],
            [
                "name" => "Configuration",
                "chart" => false,
                "chart_color" => "#eab308",
            ],
        ])->map(fn($data) => TaskType::query()->firstOrCreate(
            ['name' => $data['name']],
            $data,
        ));

        $statuses = [
            TaskStatus::query()->firstOrCreate([
                "name" => "Open",
            ], [
                "name" => "Open",
                "color" => "info",
                "icon" => "heroicon-o-clock",
                "order" => 1,
                "default" => true,
                "completed" => false,
            ]),
            TaskStatus::query()->firstOrCreate([
                "name" => "In Progress",
            ], [
                "name" => "In Progress",
                "color" => "primary",
                "icon" => "heroicon-o-arrow-path",
                "order" => 2,
                "default" => false,
                "completed" => false,
            ]),
            TaskStatus::query()->firstOrCreate([
                "name" => "Blocked",
            ], [
                "name" => "Blocked",
                "color" => "warning",
                "icon" => "heroicon-o-exclamation-circle",
                "order" => 3,
                "default" => false,
                "completed" => false,
            ]),
            TaskStatus::query()->firstOrCreate([
                "name" => "Completed",
            ], [
                "name" => "Completed",
                "color" => "success",
                "icon" => "heroicon-o-check-circle",
                "order" => 4,
                "default" => false,
                "completed" => true,
            ]),
            TaskStatus::query()->firstOrCreate([
                "name" => "Cancelled",
            ], [
                "name" => "Cancelled",
                "color" => "danger",
                "icon" => "heroicon-o-x-circle",
                "order" => 5,
                "default" => false,
                "completed" => false,
            ]),
        ];

        $users = User::all();
        $inventories = Inventory::query()
            ->where('scope_id', $this->scopeId)
            ->inRandomOrder()
            ->limit(30)
            ->get();
        $openStatus = collect($statuses)->firstWhere("name", "Open");
        $inProgress = collect($statuses)->firstWhere("name", "In Progress");
        $completed = collect($statuses)->firstWhere("name", "Completed");

        $descriptions = [
            "Preventive maintenance check",
            "OS update and driver installation",
            "Replace faulty keyboard",
            "Network cable replacement",
            "Configure VPN client",
            "Memory upgrade",
            "SSD replacement",
            "Battery replacement",
            "Monitor calibration",
            "Security patch installation",
            "Antivirus definition update",
            "BIOS firmware update",
            "WiFi adapter configuration",
            "Printer driver installation",
            "Data backup verification",
            "Hardware diagnostic scan",
            "Thermal paste replacement",
            "Cable management and labelling",
            "Power supply unit check",
            "Network switch port configuration",
        ];

        $workStart = env("ZPH_TIME_START_WORK", "07:30");
        $workEnd = env("ZPH_TIME_END_WORK", "13:30");
        $workDays = array_map(
            "intval",
            explode(",", env("ZPH_WORK_DAYS", "1,2,3,4,5,6")),
        );

        foreach (range(1, 30) as $i) {
            $statusWeights = [
                $openStatus->id => 35,
                $inProgress->id => 25,
                collect($statuses)->firstWhere("name", "Blocked")->id => 10,
                $completed->id => 25,
                collect($statuses)->firstWhere("name", "Cancelled")->id => 5,
            ];
            $statusId = $this->weightedRandom($statusWeights);

            $startsAt = $this->randomWorkDateTime(
                "-4 months",
                "+1 month",
                $workStart,
                $workEnd,
                $workDays,
            );
            $endsAt = clone $startsAt;
            $endsAt->modify("+" . rand(1, 4) . " hours");

            // Cap ends_at at end of working day
            [$endHour, $endMin] = array_map("intval", explode(":", $workEnd));
            $workEndTime = clone $startsAt;
            $workEndTime->setTime($endHour, $endMin);
            if ($endsAt > $workEndTime) {
                $endsAt = $workEndTime;
            }
            $isCompleted = $statusId === $completed->id;

            $task = Task::create([
                "scope_id" => $this->scopeId,
                "starts_at" => $startsAt,
                "ends_at" =>
                    $isCompleted || fake()->boolean(40) ? $endsAt : null,
                "all_day" => fake()->boolean(15),
                "task_type_id" => $types->random()->id,
                "task_status_id" => $statusId,
                "user_id" => $users->random()->id,
                "description" => $descriptions[($i - 1) % count($descriptions)],
                "note" => fake()->optional(0.25)->sentence(),
            ]);

            // Attach 1–3 inventory items to each task
            if ($inventories->isNotEmpty()) {
                $task
                    ->inventories()
                    ->attach(
                        $inventories
                            ->random(min(rand(1, 3), $inventories->count()))
                            ->pluck("id")
                            ->toArray(),
                    );
            }
        }
    }

    /** @param array<int> $workDays ISO day of week (1=Mon … 7=Sun) */
    private function randomWorkDateTime(
        string $startRange,
        string $endRange,
        string $workStart,
        string $workEnd,
        array $workDays,
    ): \DateTime {
        $date = fake()->dateTimeBetween($startRange, $endRange);

        while (!in_array((int) $date->format("N"), $workDays)) {
            $date->modify("+1 day");
        }

        [$startHour, $startMin] = array_map("intval", explode(":", $workStart));
        [$endHour, $endMin] = array_map("intval", explode(":", $workEnd));

        $startMinutes = $startHour * 60 + $startMin;
        $endMinutes = $endHour * 60 + $endMin;

        $randomMinutes = rand($startMinutes, $endMinutes - 60);
        $date->setTime(intdiv($randomMinutes, 60), $randomMinutes % 60);

        return $date;
    }

    /** @param array<int, int> $weights [id => weight] */
    private function weightedRandom(array $weights): int
    {
        $total = array_sum($weights);
        $random = rand(1, $total);
        $accumulated = 0;

        foreach ($weights as $id => $weight) {
            $accumulated += $weight;
            if ($random <= $accumulated) {
                return $id;
            }
        }

        return array_key_first($weights);
    }
}
