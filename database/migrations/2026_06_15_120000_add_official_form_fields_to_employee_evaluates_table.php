<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_evaluates', function (Blueprint $table) {
            $table->date('evaluation_date')->nullable()->after('position');
            $table->string('present_position')->nullable()->after('evaluation_date');
            $table->string('college_department')->nullable()->after('present_position');
            $table->boolean('education_met')->nullable()->after('college_department');
            $table->boolean('experience_met')->nullable()->after('education_met');
            $table->boolean('eligibility_met')->nullable()->after('experience_met');
            $table->boolean('training_met')->nullable()->after('eligibility_met');
            $table->decimal('minimum_requirement_score', 6, 2)->default(0)->after('training_met');
            $table->json('education_ratings')->nullable()->after('education_score');
            $table->json('training_ratings')->nullable()->after('training_score');
        });
    }

    public function down()
    {
        Schema::table('employee_evaluates', function (Blueprint $table) {
            $table->dropColumn([
                'evaluation_date',
                'present_position',
                'college_department',
                'education_met',
                'experience_met',
                'eligibility_met',
                'training_met',
                'minimum_requirement_score',
                'education_ratings',
                'training_ratings',
            ]);
        });
    }
};
