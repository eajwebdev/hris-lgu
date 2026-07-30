<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two Comparative Assessment columns the application form never collected.
 *
 * The PSB sheet asks for each candidate's PRESENT POSITION TITLE / SG / STATUS
 * and a PERFORMANCE RATING worth 35 of its 100 points. Nothing supplied either,
 * so the heaviest column on the form was always blank.
 *
 * Both only apply to someone already employed here, so rather than four text
 * boxes an applicant fills in about themselves, an internal applicant gives
 * their Employee ID and the details are read from the 201 file. The columns
 * below are the snapshot taken at application time: a promotion or a re-grade
 * afterwards must not rewrite what the board assessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applications')) {
            return;
        }

        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'employee_id')) {
                // Nullable: external applicants have no employee record, which
                // is also what makes the performance rating inapplicable.
                $table->unsignedBigInteger('employee_id')->nullable()->after('jid')->index();
            }
            if (! Schema::hasColumn('applications', 'present_position')) {
                $table->string('present_position')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('applications', 'salary_grade')) {
                $table->string('salary_grade', 50)->nullable()->after('present_position');
            }
            if (! Schema::hasColumn('applications', 'appointment_status')) {
                $table->string('appointment_status', 100)->nullable()->after('salary_grade');
            }
            if (! Schema::hasColumn('applications', 'performance_rating')) {
                // The IPCR figure, stored on its own scale; PsbScoring rescales
                // it onto the 35-point column.
                $table->decimal('performance_rating', 6, 2)->nullable()->after('appointment_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('applications')) {
            return;
        }

        Schema::table('applications', function (Blueprint $table) {
            foreach (['performance_rating', 'appointment_status', 'salary_grade', 'present_position', 'employee_id'] as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
