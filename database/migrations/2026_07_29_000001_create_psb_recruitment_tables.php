<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recruitment tables for the two LGU Mabinay forms.
 *
 *  - Position Description Form, DBM-CSC Form No. 1 (Revised 2017): a standing
 *    document describing a plantilla item, reused every time the item is
 *    posted, so job_hirings points at one rather than owning it.
 *  - Personnel Selection Board: the Comparative Assessment Form, which is the
 *    consolidation sheet, and the signatory block that prints under it.
 *
 * Points are decimal(6,2): every weight set on these forms totals exactly 100,
 * and a rounded integer would not add up.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------- Position Description
        if (! Schema::hasTable('position_descriptions')) {
            Schema::create('position_descriptions', function (Blueprint $table) {
                $table->id();

                // 1-3
                $table->string('position_title');
                $table->string('parenthetical_title')->nullable();
                $table->string('item_number')->nullable()->index();
                $table->string('salary_grade')->nullable();

                // 4-8
                $table->string('lgu_unit_and_class')->nullable();
                $table->string('department_agency')->nullable();
                $table->string('bureau_office')->nullable();
                $table->string('division_branch')->nullable();
                $table->string('workstation')->nullable();

                // 9-12
                $table->string('present_approp_act')->nullable();
                $table->string('previous_approp_act')->nullable();
                $table->string('salary_authorized')->nullable();
                $table->string('other_compensation')->nullable();

                // 13-14
                $table->string('immediate_supervisor_title')->nullable();
                $table->string('next_higher_supervisor_title')->nullable();

                // 16
                $table->text('equipment_used')->nullable();

                // 17-18. Checkbox grids; stored as JSON because the form asks
                // for a fixed matrix of ticks, not rows anyone queries.
                $table->json('contacts')->nullable();
                $table->json('working_conditions')->nullable();

                // 19-20
                $table->text('unit_general_function')->nullable();
                $table->text('position_general_function')->nullable();

                // 21a-21f
                $table->text('qs_education')->nullable();
                $table->text('qs_experience')->nullable();
                $table->text('qs_training')->nullable();
                $table->text('qs_eligibility')->nullable();
                $table->json('core_competencies')->nullable();
                $table->json('leadership_competencies')->nullable();

                $table->string('status')->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // 22. Duties, with the percentage of working time each takes.
        if (! Schema::hasTable('position_description_duties')) {
            Schema::create('position_description_duties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('position_description_id')->constrained()->cascadeOnDelete();
                $table->decimal('percentage', 5, 2)->default(0);
                $table->text('duty');
                $table->string('competency_level')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 15. Positions directly supervised.
        if (! Schema::hasTable('position_description_supervised')) {
            Schema::create('position_description_supervised', function (Blueprint $table) {
                $table->id();
                $table->foreignId('position_description_id')->constrained()->cascadeOnDelete();
                $table->string('position_title');
                $table->string('item_number')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // A posting reuses a standing Position Description.
        if (Schema::hasTable('job_hirings') && ! Schema::hasColumn('job_hirings', 'position_description_id')) {
            Schema::table('job_hirings', function (Blueprint $table) {
                $table->unsignedBigInteger('position_description_id')->nullable()->after('id')->index();
            });
        }

        // ------------------------------------------------------ PSB members
        if (! Schema::hasTable('psb_members')) {
            Schema::create('psb_members', function (Blueprint $table) {
                $table->id();
                // Optional link to an employee; the printed block uses the
                // stored name so a board survives the person leaving.
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->string('name');
                $table->string('credentials')->nullable();   // "RN, JD", "Ph.D."
                $table->string('role')->default('Member');   // Chairperson | Vice-Chairperson | Member
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // ------------------------------------------- Comparative Assessment
        if (! Schema::hasTable('comparative_assessments')) {
            Schema::create('comparative_assessments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('jid')->index();          // job_hirings
                $table->unsignedBigInteger('ete_id')->nullable();
                $table->unsignedBigInteger('interview_id')->nullable();

                // Form header block.
                $table->string('position_to_be_filled')->nullable();
                $table->string('item_no')->nullable();
                $table->string('location')->nullable();
                $table->date('date_posted')->nullable();
                $table->date('date_published')->nullable();
                $table->string('rate_per_month')->nullable();

                $table->timestamp('finalised_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comparative_assessment_rows')) {
            Schema::create('comparative_assessment_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('comparative_assessment_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('application_id')->nullable()->index();

                $table->string('candidate_name');
                $table->string('present_position')->nullable();
                $table->string('salary_grade')->nullable();
                $table->string('appointment_status')->nullable();
                $table->string('civil_service_eligibility')->nullable();

                // Preliminary evaluation. The six weights below total 100.
                $table->decimal('performance_rating', 6, 2)->nullable();   // 35
                $table->decimal('education_points', 6, 2)->nullable();     // 15
                $table->decimal('training_points', 6, 2)->nullable();      // 10
                $table->decimal('experience_points', 6, 2)->nullable();    // 20
                $table->decimal('potential_points', 6, 2)->nullable();     // 10
                $table->decimal('psychosocial_points', 6, 2)->nullable();  // 10
                $table->decimal('preliminary_total', 6, 2)->nullable();    // 100

                // Further assessment: written exam / skills test / etc.
                $table->decimal('further_assessment', 6, 2)->nullable();
                $table->string('further_assessment_label')->nullable();

                $table->decimal('overall_points', 6, 2)->nullable();
                $table->unsignedInteger('rank')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comparative_assessment_rows');
        Schema::dropIfExists('comparative_assessments');
        Schema::dropIfExists('psb_members');
        Schema::dropIfExists('position_description_supervised');
        Schema::dropIfExists('position_description_duties');

        if (Schema::hasTable('job_hirings') && Schema::hasColumn('job_hirings', 'position_description_id')) {
            Schema::table('job_hirings', function (Blueprint $table) {
                $table->dropColumn('position_description_id');
            });
        }

        Schema::dropIfExists('position_descriptions');
    }
};
