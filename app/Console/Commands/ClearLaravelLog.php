<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLaravelLog extends Command
{
    protected $signature = 'log:clear';
    protected $description = 'Clear the laravel.log file';

    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');

        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
            $this->info('Laravel log cleared successfully.');
        } else {
            $this->info('Log file does not exist.');
        }
    }
}
