<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The selection board as it signed one particular Comparative Assessment.
 *
 * The board under Settings is only the starting point: when an assessment is
 * built, that list is copied in here and from then on belongs to this job
 * hiring alone. Editing a name on a sheet must not rewrite the employee
 * record, the standing board, or any assessment signed before it — a form the
 * board has already signed has to keep printing the names that signed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comparative_assessment_board_members')) {
            return;
        }

        Schema::create('comparative_assessment_board_members', function (Blueprint $table) {
            $table->id();

            // The constraint is named explicitly: Laravel would derive
            // "comparative_assessment_board_members_comparative_assessment_id_foreign",
            // which is 70 characters and past MySQL's 64-character identifier
            // limit, so the key is silently skipped and deletes stop cascading.
            $table->unsignedBigInteger('comparative_assessment_id');
            $table->foreign('comparative_assessment_id', 'cabm_assessment_fk')
                ->references('id')->on('comparative_assessments')
                ->cascadeOnDelete();

            // Where the name came from, kept only so the picker can show it
            // again. The printed block always uses the typed name below.
            $table->unsignedBigInteger('employee_id')->nullable();

            $table->string('name');
            $table->string('credentials')->nullable();
            $table->string('role')->default('Member');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparative_assessment_board_members');
    }
};
