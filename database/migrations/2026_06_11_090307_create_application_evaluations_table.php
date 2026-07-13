<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->date('evaluation_date')->nullable();

            // Applicant Information Snapshot
            $table->string('present_position')->nullable();
            $table->string('college_department')->nullable();

            // Minimum Requirements (70%)
            $table->boolean('education_met')->default(false);
            $table->boolean('experience_met')->default(false);
            $table->boolean('eligibility_met')->default(false);
            $table->boolean('training_met')->default(false);

            // Additional Credits
            $table->decimal('education_score', 5, 2)->default(0);
            $table->decimal('training_score', 5, 2)->default(0);
            $table->decimal('experience_score', 5, 2)->default(0);

            // Experience Range
            $table->year('experience_year_from')->nullable();
            $table->year('experience_year_to')->nullable();

            $table->json('experience_credits')->nullable();

            // Scores
            $table->decimal('minimum_requirement_score', 5, 2)->default(70);
            $table->decimal('total_rating', 5, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Prevent duplicate evaluation assignment
            $table->unique(['application_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_evaluations');
    }
};