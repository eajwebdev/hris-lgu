<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_panels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->unsignedBigInteger('emp_id');
            $table->timestamps();

            $table->unique(['interview_id', 'emp_id']);
            $table->foreign('interview_id')->references('id')->on('interview_evaluations')->cascadeOnDelete();
            $table->foreign('emp_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_panels');
    }
};
