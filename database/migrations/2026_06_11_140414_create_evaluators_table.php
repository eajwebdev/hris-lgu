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
        Schema::create('evaluators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ete_id');
            $table->unsignedBigInteger('emp_id');
            $table->timestamps();

            $table->unique(['ete_id', 'emp_id']);

            $table->foreign('ete_id')
                ->references('id')
                ->on('ete_evaluations')
                ->onDelete('cascade');

            $table->foreign('emp_id')
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
        Schema::dropIfExists('evaluators');
    }
};
