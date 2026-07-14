<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The places employees are expected to clock in from.
 *
 * A station is a point plus a radius. Punches are never blocked by distance —
 * the policy is "clock in from anywhere, but HR sees who was far from every
 * station", so the geometry only ever feeds an indicator, not a refusal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('radius_m')->default(150);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_stations');
    }
};
