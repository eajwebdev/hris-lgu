<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The stub-table migration (2026_06_12) recreated leave_applications with every
 * workflow column nullable and no default. The leave screens and HR's queues
 * all compare those columns to numbers (status = 1, emp_esign = 0,
 * remarks_stat = 0), and NULL never equals anything in SQL, so a freshly filed
 * application sat outside every queue: HR's Approve button never showed and
 * the transmittal report came back empty.
 *
 * This puts the defaults back and fills in rows already saved without them.
 * The PDS tables get the same treatment for their review status, which the
 * pending queues read as status = 0.
 */
return new class extends Migration
{
    private array $leaveDefaults = [
        'status' => "'1'",
        'emp_esign' => "'0'",
        'remarks_stat' => "'0'",
        'holiday' => "'0'",
        'day_wpay' => '0',
    ];

    private array $pdsTables = ['eligibilities', 'work_experiences', 'voluntary_works', 'learning_devs'];

    public function up(): void
    {
        if (Schema::hasTable('leave_applications')) {
            foreach ($this->leaveDefaults as $column => $default) {
                if (! Schema::hasColumn('leave_applications', $column)) {
                    continue;
                }
                DB::table('leave_applications')->whereNull($column)->update([$column => trim($default, "'")]);
                if (DB::getDriverName() === 'mysql') {
                    DB::statement("ALTER TABLE leave_applications ALTER COLUMN `{$column}` SET DEFAULT {$default}");
                }
            }
        }

        foreach ($this->pdsTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::table($table)->whereNull('status')->update(['status' => '0']);
                if (DB::getDriverName() === 'mysql') {
                    DB::statement("ALTER TABLE `{$table}` ALTER COLUMN `status` SET DEFAULT '0'");
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('leave_applications')) {
            foreach (array_keys($this->leaveDefaults) as $column) {
                if (Schema::hasColumn('leave_applications', $column)) {
                    DB::statement("ALTER TABLE leave_applications ALTER COLUMN `{$column}` DROP DEFAULT");
                }
            }
        }

        foreach ($this->pdsTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::statement("ALTER TABLE `{$table}` ALTER COLUMN `status` DROP DEFAULT");
            }
        }
    }
};
