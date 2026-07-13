<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('panel_employee_id');
            $table->json('interview_scores')->nullable();
            $table->json('potential_scores')->nullable();
            $table->decimal('interview_total', 6, 2)->default(0);
            $table->decimal('potential_total', 6, 2)->default(0);
            $table->decimal('total_score', 6, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['interview_id', 'application_id', 'panel_employee_id'], 'interview_ratings_unique_panel');
            $table->foreign('interview_id')->references('id')->on('interview_evaluations')->cascadeOnDelete();
            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('panel_employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_ratings');
    }
};
