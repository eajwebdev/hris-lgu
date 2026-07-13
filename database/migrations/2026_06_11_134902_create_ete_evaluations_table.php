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
        Schema::create('ete_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jid');
            $table->string('experience_years')->nullable();
            $table->dateTime('evaluation_date')->nullable();
            $table->unsignedBigInteger('active_application_id')->nullable();

            $table->timestamps();

            $table->foreign('jid')
                ->references('id')
                ->on('job_hirings')
                ->onDelete('cascade');

            $table->foreign('active_application_id')
                ->references('id')
                ->on('applications')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ete_evaluations');
    }
};
