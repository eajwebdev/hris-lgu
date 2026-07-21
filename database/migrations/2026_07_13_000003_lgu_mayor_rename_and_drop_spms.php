<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LGU alignment:
 *  - The approving official is the Mayor or the Vice Mayor, whoever is
 *    available. The leave form therefore keeps ONE approval slot
 *    (approver_*) and records which official signed in approver_role,
 *    rather than a column named after a single office.
 *  - The SPMS / Drive module is removed; its tables are dropped.
 *
 * Raw ALTER statements are used because doctrine/dbal is not a project
 * dependency, so Blueprint::renameColumn() is unavailable.
 */
return new class extends Migration
{
    public function up()
    {
        // --- settings: SUC President / VPs -> Mayor / Vice Mayor -------------
        if (Schema::hasColumn('settings', 'suc_pres')) {
            DB::statement('ALTER TABLE `settings` CHANGE `suc_pres` `mayor` INT(11) NULL');
        }
        if (Schema::hasColumn('settings', 'vpaa')) {
            DB::statement('ALTER TABLE `settings` CHANGE `vpaa` `vice_mayor` INT(11) NULL');
        }
        if (Schema::hasColumn('settings', 'vpaf')) {
            Schema::table('settings', fn (Blueprint $t) => $t->dropColumn('vpaf'));
        }

        // --- leave_applications: president -> approver -----------------------
        $renames = [
            'president'   => ['approver', 'INT(11) NULL'],
            'pres_prefix' => ['approver_prefix', 'VARCHAR(255) NULL'],
            'pres_sign'   => ['approver_sign', 'INT(11) NULL'],
            'pres_sdate'  => ['approver_sdate', 'VARCHAR(255) NULL'],
        ];

        foreach ($renames as $from => [$to, $type]) {
            if (Schema::hasColumn('leave_applications', $from)) {
                DB::statement("ALTER TABLE `leave_applications` CHANGE `{$from}` `{$to}` {$type}");
            }
        }

        // Which official actually signed: 'Mayor' or 'Vice Mayor'.
        if (!Schema::hasColumn('leave_applications', 'approver_role')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                if (Schema::hasColumn('leave_applications', 'approver')) {
                    $table->string('approver_role')->nullable()->after('approver');
                } else {
                    $table->string('approver_role')->nullable();
                }
            });
        }

        // --- drop the SPMS / Drive tables ------------------------------------
        foreach ($this->spmsTables() as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('settings', 'mayor')) {
            DB::statement('ALTER TABLE `settings` CHANGE `mayor` `suc_pres` INT(11) NULL');
        }
        if (Schema::hasColumn('settings', 'vice_mayor')) {
            DB::statement('ALTER TABLE `settings` CHANGE `vice_mayor` `vpaa` INT(11) NULL');
        }
        if (!Schema::hasColumn('settings', 'vpaf')) {
            Schema::table('settings', fn (Blueprint $t) => $t->integer('vpaf')->nullable());
        }

        $reverts = [
            'approver'        => ['president', 'INT(11) NULL'],
            'approver_prefix' => ['pres_prefix', 'VARCHAR(255) NULL'],
            'approver_sign'   => ['pres_sign', 'INT(11) NULL'],
            'approver_sdate'  => ['pres_sdate', 'VARCHAR(255) NULL'],
        ];

        foreach ($reverts as $from => [$to, $type]) {
            if (Schema::hasColumn('leave_applications', $from)) {
                DB::statement("ALTER TABLE `leave_applications` CHANGE `{$from}` `{$to}` {$type}");
            }
        }

        if (Schema::hasColumn('leave_applications', 'approver_role')) {
            Schema::table('leave_applications', fn (Blueprint $t) => $t->dropColumn('approver_role'));
        }

        // The dropped SPMS tables are not recreated; restore from a dump if needed.
    }

    private function spmsTables(): array
    {
        return [
            'opcr_mfo_data', 'opcr_mfos', 'opcrs',
            'dpcr_mfo_data', 'dpcr_mfos', 'dpcrs',
            'ipcr_mfo_data', 'ipcr_mfos', 'ipcrs',
            'spms_asignatories', 'spms_comments', 'spms_personnels',
            'evidence', 'documents', 'docu_folders',
            'pmts', 'deans', 'dpipops', 'pr_data', 'pr_settings',
        ];
    }
};
