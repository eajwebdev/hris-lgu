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
        Schema::create('emp_ployees', function (Blueprint $table) {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('mname')->nullable();
            $table->string('lname')->nullable();
            $table->string('position')->nullable();
            $table->string('profile')->default('default.png');
            $table->unsignedBigInteger('camp_id')->nullable();
            $table->string('emp_ID')->nullable();
            $table->string('emp_status')->nullable();
            $table->string('emp_dept')->nullable();
            $table->string('item_no')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('employee');
            $table->date('date_hired')->nullable();
            $table->string('prefix')->nullable();
            $table->string('title_prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->date('bdate')->nullable();
            $table->integer('age')->nullable();
            $table->string('b_place')->nullable();
            $table->string('sex')->nullable();
            $table->string('civil_status')->nullable();
            $table->float('height_cm')->nullable();
            $table->float('height_ft')->nullable();
            $table->float('weight_kg')->nullable();
            $table->float('weight_lb')->nullable();
            $table->string('b_type')->nullable();
            $table->string('gsis')->nullable();
            $table->string('pagibig')->nullable();
            $table->string('philhealth')->nullable();
            $table->string('sss')->nullable();
            $table->string('tin')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('telephone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('org_email')->nullable();
            $table->string('add_block')->nullable();
            $table->string('add_street')->nullable();
            $table->string('add_village')->nullable();
            $table->string('add_brgy')->nullable();
            $table->string('add_city')->nullable();
            $table->string('add_region')->nullable();
            $table->string('add_prov')->nullable();
            $table->string('add_zcode')->nullable();
            $table->string('padd_block')->nullable();
            $table->string('padd_street')->nullable();
            $table->string('padd_village')->nullable();
            $table->string('padd_brgy')->nullable();
            $table->string('padd_city')->nullable();
            $table->string('padd_region')->nullable();
            $table->string('padd_prov')->nullable();
            $table->string('padd_zcode')->nullable();
            $table->string('stat_1')->nullable();
            $table->binary('f1')->nullable();
            $table->binary('f2')->nullable();
            $table->binary('f3')->nullable();
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
        Schema::dropIfExists('emp_ployees');
    }
};
