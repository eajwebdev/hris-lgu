<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ete_id');
            $table->unsignedBigInteger('jid');
            $table->dateTime('interview_date')->nullable();
            $table->unsignedBigInteger('active_application_id')->nullable();
            $table->timestamps();

            $table->foreign('ete_id')->references('id')->on('ete_evaluations')->cascadeOnDelete();
            $table->foreign('jid')->references('id')->on('job_hirings')->cascadeOnDelete();
            $table->foreign('active_application_id')->references('id')->on('applications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_evaluations');
    }
};
