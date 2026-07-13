<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_evaluates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ete_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('jid');
            $table->unsignedBigInteger('evaluator_id');
            $table->string('position')->nullable();
            $table->decimal('education_score', 6, 2)->default(0);
            $table->decimal('training_score', 6, 2)->default(0);
            $table->decimal('experience_score', 6, 2)->default(0);
            $table->json('experience_year_ratings')->nullable();
            $table->decimal('total_score', 6, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['ete_id', 'application_id', 'evaluator_id'], 'employee_evaluates_unique_panel');

            $table->foreign('ete_id')
                ->references('id')
                ->on('ete_evaluations')
                ->onDelete('cascade');

            $table->foreign('application_id')
                ->references('id')
                ->on('applications')
                ->onDelete('cascade');

            $table->foreign('jid')
                ->references('id')
                ->on('job_hirings')
                ->onDelete('cascade');

            $table->foreign('evaluator_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_evaluates');
    }
};
