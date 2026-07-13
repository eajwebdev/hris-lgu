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
        Schema::create('eligibilities', function (Blueprint $table) {
            $table->id(); 
            $table->string('empid');
            $table->string('career_eligible')->nullable();
            $table->decimal('rating')->nullable();
            $table->date('date_exam')->nullable();
            $table->string('place_exam')->nullable();
            $table->string('number')->nullable();
            $table->date('date_valid')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('eligibilities');
    }
};
