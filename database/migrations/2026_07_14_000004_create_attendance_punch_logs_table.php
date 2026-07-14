<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per successful portal punch, carrying where it happened.
 *
 * The DTR keeps its legacy comma-separated shape and every report that reads it
 * stays untouched; this table is the geo audit trail beside it. station_name is
 * denormalised on purpose — the row must still say "Main Hall" years after that
 * station is renamed or deleted.
 *
 * out_of_range is three-valued: 1 = outside every station's radius, 0 = inside
 * one, NULL = undeterminable (no location shared, or no stations configured).
 * HR's indicator needs the difference between "was far away" and "we don't know".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_punch_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('emp_ID', 32);
            $table->string('action', 8);          // in | out
            $table->string('mode', 8);            // face | qr
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('accuracy_m')->nullable();
            $table->unsignedBigInteger('station_id')->nullable();
            $table->string('station_name', 100)->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->boolean('out_of_range')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'out_of_range']);
            $table->index('emp_ID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_punch_logs');
    }
};
