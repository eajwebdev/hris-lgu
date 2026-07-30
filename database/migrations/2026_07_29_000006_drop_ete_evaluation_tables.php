<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the old ETE (Education / Training / Experience) evaluation process.
 *
 * It was a separate scoring sheet that predates the LGU's own forms. Under the
 * Personnel Selection Board forms there is no ETE sheet: education, training
 * and experience are columns on the Comparative Assessment itself, worth
 * 15 / 10 / 20 of its 100 points, and the board scores them there.
 *
 * The interview no longer hangs off an ETE record either — it belongs to the
 * vacancy — so the ete_id columns go with the tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The column carries a foreign key, and MySQL will not drop a column
        // whose index a constraint still needs. Clear the constraint first,
        // looking its name up rather than assuming Laravel's convention.
        foreach (['interview_evaluations' => 'ete_id', 'comparative_assessments' => 'ete_id'] as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $constraints = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL',
                [DB::getDatabaseName(), $table, $column]
            );

            foreach ($constraints as $fk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($column));
        }

        // Children first — each of these holds a foreign key into
        // ete_evaluations, and MySQL refuses to drop a parent while they do.
        // employee_evaluates and evaluators are the panel-assignment tables of
        // the same retired process; nothing in the application reads them and
        // their models are gone.
        Schema::dropIfExists('employee_evaluates');
        Schema::dropIfExists('evaluators');
        Schema::dropIfExists('ete_applicant_ratings');
        Schema::dropIfExists('ete_evaluations');
        Schema::dropIfExists('application_evaluations');
    }

    public function down(): void
    {
        // The ETE process is retired. Recreating the columns is enough to roll
        // the schema back; the tables themselves are not restored, because
        // nothing in the application reads them any more.
        foreach (['interview_evaluations', 'comparative_assessments'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'ete_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('ete_id')->nullable());
            }
        }
    }
};
