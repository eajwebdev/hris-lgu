<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only trail of who touched whose biometric data, and from where.
 *
 * Biometrics are sensitive personal information under the Data Privacy Act, so
 * every registration and removal is recorded whether or not anyone ever reads
 * it back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('action', 32);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('performed_by_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_audit_logs');
    }
};
