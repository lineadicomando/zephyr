<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class DbCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checking and restoring database consistency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(__('Checking and restoring database consistency'));
        foreach (File::allFiles(app_path('Models')) as $file) {
            $name = Str::replaceLast('.php', '', $file->getFilename());
            $model = 'App\\Models\\' . $name;
            if (class_exists($model) && method_exists($model, 'dbCheck')) {
                $model::dbCheck(true);
            }
        }
    }
}
