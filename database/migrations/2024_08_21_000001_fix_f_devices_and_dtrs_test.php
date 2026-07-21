<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('f_devices')) {
            Schema::table('f_devices', function (Blueprint $table) {
                if (!Schema::hasColumn('f_devices', 'area_id')) {
                    $table->string('area_id')->nullable();
                }
            });
        }

        if (!Schema::hasTable('dtrs_test')) {
            Schema::create('dtrs_test', function (Blueprint $table) {
                $table->id();
                $table->string('emp_ID')->nullable();
                $table->date('date')->nullable();
                $table->string('time_in')->nullable();
                $table->string('time_out')->nullable();
                $table->string('time_over')->nullable();
                $table->string('device_id')->nullable();
                $table->string('device_id_in')->nullable();
                $table->string('device_id_out')->nullable();
                $table->string('device_id_over')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtrs_test');
    }
};
