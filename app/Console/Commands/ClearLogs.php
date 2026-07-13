<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLogs extends Command
{
    /**
     * Command name (run in terminal)
     *
     * Usage:
     *   php artisan logs:clear
     */
    protected $signature = 'logs:clear';

    /**
     * Description
     */
    protected $description = 'Empty all Laravel log files in storage/logs';

    /**
     * Execute command
     */
    public function handle(): int
    {
        $logPath = storage_path('logs');

        if (!is_dir($logPath)) {
            $this->error("❌ Log directory not found: {$logPath}");
            return self::FAILURE;
        }

        $files = glob($logPath . DIRECTORY_SEPARATOR . '*.log');

        if (empty($files)) {
            $this->info('✅ No log files to clear.');
            return self::SUCCESS;
        }

        $cleared = 0;
        foreach ($files as $file) {
            // Truncate the file to 0 bytes instead of deleting it, so the
            // logger can keep writing to the same handle without issues.
            if (file_put_contents($file, '') !== false) {
                $this->line('  ... cleared ' . basename($file));
                $cleared++;
            } else {
                $this->warn('  ... could not clear ' . basename($file));
            }
        }

        $this->newLine();
        $this->info("✅ Laravel logs cleared! ({$cleared} file(s))");

        return self::SUCCESS;
    }
}
