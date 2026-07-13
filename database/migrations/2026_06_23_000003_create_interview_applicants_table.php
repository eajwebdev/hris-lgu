<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_applicants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->unsignedBigInteger('application_id');
            $table->boolean('is_cast')->default(false);
            $table->dateTime('casted_at')->nullable();
            $table->dateTime('uncasted_at')->nullable();
            $table->timestamps();

            $table->unique(['interview_id', 'application_id']);
            $table->foreign('interview_id')->references('id')->on('interview_evaluations')->cascadeOnDelete();
            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_applicants');
    }
};
