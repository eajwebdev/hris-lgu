<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSql extends Command
{
    /**
     * Command name (THIS is what you run in terminal)
     *
     * Usage:
     *   php artisan db:import-sql
     *   php artisan db:import-sql path/to/file.sql
     *   php artisan db:import-sql --connection=payroll
     */
    protected $signature = 'db:import-sql
                            {file? : Path to the .sql file (defaults to storage/app/dbcpsuhris.sql)}
                            {--connection= : DB connection to import into (defaults to the default connection)}
                            {--fresh : Drop all existing tables in the target database before importing}';

    /**
     * Description
     */
    protected $description = 'Import a (large) SQL dump file, executed statement-by-statement';

    /**
     * Execute command
     */
    public function handle(): int
    {
        $file = $this->argument('file') ?: storage_path('app/dbcpsuhris.sql');

        // Allow relative paths from the project root too.
        if (!file_exists($file) && file_exists(base_path($file))) {
            $file = base_path($file);
        }

        if (!file_exists($file)) {
            $this->error("❌ SQL file not found: {$file}");
            return self::FAILURE;
        }

        $connection = $this->option('connection');
        $db = DB::connection($connection ?: null);
        $pdo = $db->getPdo();

        $sizeMb = round(filesize($file) / 1048576, 1);
        $this->info("📥 Importing {$file} ({$sizeMb} MB) into '{$db->getDatabaseName()}'...");

        set_time_limit(0);
        @ini_set('memory_limit', '-1');

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error("❌ Could not open file for reading: {$file}");
            return self::FAILURE;
        }

        // Speed + avoid FK ordering problems during a full-database restore.
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('SET UNIQUE_CHECKS=0');
        $pdo->exec('SET AUTOCOMMIT=0');

        if ($this->option('fresh')) {
            $this->dropAllTables($db, $pdo);
        }

        $buffer     = '';
        $statements = 0;
        $lineNo     = 0;
        $delimiter  = ';';

        try {
            while (($line = fgets($handle)) !== false) {
                $lineNo++;
                $trimmed = ltrim($line);

                // Skip blank lines and comments (phpMyAdmin uses --, #, and /* */).
                if ($trimmed === '' || $trimmed === "\n"
                    || str_starts_with($trimmed, '--')
                    || str_starts_with($trimmed, '#')
                    || str_starts_with($trimmed, '/*')) {
                    continue;
                }

                // Honor DELIMITER changes (routines/triggers), just in case.
                if (preg_match('/^DELIMITER\s+(\S+)/i', $trimmed, $m)) {
                    $delimiter = $m[1];
                    continue;
                }

                $buffer .= $line;

                // A statement ends when a line ends with the active delimiter.
                // phpMyAdmin escapes newlines inside string values, so a physical
                // line ending in the delimiter is a reliable statement boundary.
                if (str_ends_with(rtrim($line), $delimiter)) {
                    $sql = trim($buffer);
                    $sql = rtrim($sql, $delimiter);
                    $buffer = '';

                    if ($sql === '') {
                        continue;
                    }

                    $pdo->exec($sql);
                    $statements++;

                    if ($statements % 500 === 0) {
                        $this->line("  ... {$statements} statements executed");
                    }
                }
            }

            // Execute any trailing statement without a terminating delimiter.
            $sql = trim($buffer);
            if ($sql !== '') {
                $pdo->exec(rtrim($sql, $delimiter));
                $statements++;
            }

            $pdo->exec('COMMIT');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET UNIQUE_CHECKS=1');

            fclose($handle);

            $this->newLine();
            $this->info("✅ Database imported successfully! ({$statements} statements)");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $pdo->exec('ROLLBACK');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET UNIQUE_CHECKS=1');
            fclose($handle);

            $this->newLine();
            $this->error("❌ Error near line {$lineNo}: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Drop every table in the target database (FK checks already disabled).
     */
    private function dropAllTables($db, \PDO $pdo): void
    {
        $database = $db->getDatabaseName();
        $this->warn("⚠️  --fresh: dropping all tables in '{$database}'...");

        $tables = $db->select('SHOW FULL TABLES');
        foreach ($tables as $row) {
            $values = array_values((array) $row);
            $name   = $values[0];
            $type   = $values[1] ?? 'BASE TABLE';

            $quoted = '`' . str_replace('`', '``', $name) . '`';
            $pdo->exec($type === 'VIEW' ? "DROP VIEW IF EXISTS {$quoted}" : "DROP TABLE IF EXISTS {$quoted}");
        }

        $this->line('  ... ' . count($tables) . ' tables dropped');
    }
}
