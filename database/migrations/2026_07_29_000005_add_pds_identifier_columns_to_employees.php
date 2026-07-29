<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two identifiers the current PDS prints but never had anywhere to store.
 *
 * The printable form was updated to the newer CS Form 212 layout — it asks for
 * a PhilSys Number at item 13 and a UMID at item 10 — but the data layer was
 * not brought with it:
 *
 *   - item 13 read employees.philsys, a column that does not exist, so every
 *     PDS ever generated printed "N/A" there;
 *   - item 10 was labelled UMID but rendered the SSS number, because there was
 *     no UMID column to read.
 *
 * Both columns are added here. Nothing is renamed or dropped: gsis, sss, tin,
 * pagibig and philhealth stay exactly as they are, so existing records and the
 * rest of the form are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'umid')) {
                $table->string('umid')->nullable()->after('sss');
            }
            if (! Schema::hasColumn('employees', 'philsys')) {
                $table->string('philsys')->nullable()->after('umid');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            foreach (['philsys', 'umid'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
