<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An LGU has no campuses — that was a university concept. Employees, users
 * and events are no longer scoped by one.
 *
 * The `campuses` table itself is intentionally KEPT: the biometric time-entry
 * API still groups logzones and devices by camp_id, and that contract is
 * consumed by the kiosk app. Nothing in the HRIS reads it any more.
 */
return new class extends Migration
{
    public function up()
    {
        $drops = [
            'employees'     => 'camp_id',
            'users'         => 'campus_id',
            'events'        => 'campus_id',
            'announcements' => 'camp_id',
        ];

        foreach ($drops as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }

    public function down()
    {
        $restore = [
            'employees'     => 'camp_id',
            'users'         => 'campus_id',
            'events'        => 'campus_id',
            'announcements' => 'camp_id',
        ];

        foreach ($restore as $table => $column) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $t) => $t->integer($column)->nullable());
            }
        }
    }
};
