<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('family_bgs')) {
            Schema::create('family_bgs', function (Blueprint $table) {
                $table->id();
                $table->string('empid');
                $table->string('spouse_sname')->nullable();
                $table->string('spouse_fname')->nullable();
                $table->string('spouse_mname')->nullable();
                $table->string('spouse_ext')->nullable();
                $table->text('name_child')->nullable();
                $table->text('date_birth')->nullable();
                $table->string('occupation')->nullable();
                $table->string('bus_name')->nullable();
                $table->string('bus_address')->nullable();
                $table->string('telephone')->nullable();
                $table->string('father_sname')->nullable();
                $table->string('father_fname')->nullable();
                $table->string('father_mname')->nullable();
                $table->string('father_ext')->nullable();
                $table->string('mother_maiden')->nullable();
                $table->string('mother_sname')->nullable();
                $table->string('mother_fname')->nullable();
                $table->string('mother_mname')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('educ_bgs')) {
            Schema::create('educ_bgs', function (Blueprint $table) {
                $table->id();
                $table->string('empid');
                $table->string('elem_school')->nullable();
                $table->string('elem_period')->nullable();
                $table->string('elem_level')->nullable();
                $table->string('elem_grad')->nullable();
                $table->string('elem_honor')->nullable();
                $table->string('sec_school')->nullable();
                $table->string('sec_period')->nullable();
                $table->string('sec_level')->nullable();
                $table->string('sec_grad')->nullable();
                $table->string('sec_honor')->nullable();
                $table->string('voc_school')->nullable();
                $table->string('voc_course')->nullable();
                $table->string('voc_period')->nullable();
                $table->string('voc_level')->nullable();
                $table->string('voc_grad')->nullable();
                $table->string('voc_honor')->nullable();
                $table->string('coll_school')->nullable();
                $table->string('coll_course')->nullable();
                $table->string('coll_period')->nullable();
                $table->string('coll_level')->nullable();
                $table->string('coll_grad')->nullable();
                $table->string('coll_honor')->nullable();
                $table->string('grad_school')->nullable();
                $table->string('grad_course')->nullable();
                $table->string('grad_period')->nullable();
                $table->string('grad_level')->nullable();
                $table->string('grad_grad')->nullable();
                $table->string('grad_honor')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('other_infos')) {
            Schema::create('other_infos', function (Blueprint $table) {
                $table->id();
                $table->string('empid');
                $table->text('skills_hob')->nullable();
                $table->text('recognition')->nullable();
                $table->text('mem_org')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('info_questions')) {
            Schema::create('info_questions', function (Blueprint $table) {
                $table->id();
                $table->string('empid');
                $table->text('question')->nullable();
                $table->text('qdetails')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('work_experiences')) {
            Schema::create('work_experiences', function (Blueprint $table) {
                $table->id();
                $table->string('empid');
                $table->date('date_from')->nullable();
                $table->date('date_to')->nullable();
                $table->string('position')->nullable();
                $table->string('company')->nullable();
                $table->decimal('monthly_salary', 12, 2)->nullable();
                $table->string('salary_grade')->nullable();
                $table->string('status_appointment')->nullable();
                $table->string('gov_service')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('voluntary_works')) {
            Schema::create('voluntary_works', function (Blueprint $table) {
                $table->id();
                $table->string('empid');
                $table->string('name_address_org')->nullable();
                $table->date('date_from')->nullable();
                $table->date('date_to')->nullable();
                $table->integer('number_hours')->nullable();
                $table->string('position_work')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('learning_devs')) {
            Schema::create('learning_devs', function (Blueprint $table) {
                $table->id();
                $table->string('empid');
                $table->string('title_learning')->nullable();
                $table->date('date_from')->nullable();
                $table->date('date_to')->nullable();
                $table->integer('number_hours')->nullable();
                $table->string('type_ld')->nullable();
                $table->string('conducted_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_devs');
        Schema::dropIfExists('voluntary_works');
        Schema::dropIfExists('work_experiences');
        Schema::dropIfExists('info_questions');
        Schema::dropIfExists('other_infos');
        Schema::dropIfExists('educ_bgs');
        Schema::dropIfExists('family_bgs');
    }
};
