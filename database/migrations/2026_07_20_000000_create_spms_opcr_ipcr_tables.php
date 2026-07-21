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
        // 1. OPCR Table
        if (!Schema::hasTable('spms_opcrs')) {
            Schema::create('spms_opcrs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('office_id');
                $table->unsignedBigInteger('office_head_id')->nullable();
                $table->integer('year')->default(2026);
                $table->tinyInteger('semester')->default(1); // 1 = Jan-Jun, 2 = Jul-Dec
                $table->string('status')->default('Draft'); // Draft, Submitted, Approved
                $table->decimal('total_core_score', 5, 3)->nullable();
                $table->decimal('total_support_score', 5, 3)->nullable();
                $table->decimal('final_numerical_rating', 4, 2)->nullable();
                $table->string('final_adjectival_rating', 10)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // 2. OPCR Targets (MFOs under an OPCR)
        if (!Schema::hasTable('spms_opcr_targets')) {
            Schema::create('spms_opcr_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('opcr_id');
                $table->string('category')->default('Core Functions'); // Core Functions, Support Functions, Strategic Functions
                $table->string('subcategory')->nullable(); // e.g. Operational Management, Personnel Management
                $table->text('mfo_pap'); // Major Final Output / Program, Activity, Project
                $table->text('success_indicators'); // Targets + Measures
                $table->text('actual_accomplishment')->nullable();
                $table->decimal('rating_q', 3, 2)->nullable(); // Quality (1-5)
                $table->decimal('rating_e', 3, 2)->nullable(); // Efficiency (1-5)
                $table->decimal('rating_t', 3, 2)->nullable(); // Timeliness (1-5)
                $table->decimal('rating_ave', 4, 2)->nullable(); // Average (Q+E+T)/3
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // 3. IPCR Table
        if (!Schema::hasTable('spms_ipcrs')) {
            Schema::create('spms_ipcrs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('office_id');
                $table->unsignedBigInteger('opcr_id')->nullable();
                $table->integer('year')->default(2026);
                $table->tinyInteger('semester')->default(1);
                $table->string('status')->default('Draft'); // Draft, Submitted, Reviewed, Approved
                $table->decimal('total_core_score', 5, 3)->nullable();
                $table->decimal('total_support_score', 5, 3)->nullable();
                $table->decimal('final_numerical_rating', 4, 2)->nullable();
                $table->string('final_adjectival_rating', 10)->nullable();
                $table->text('comments_recommendations')->nullable();
                $table->timestamps();
            });
        }

        // 4. IPCR Targets (Individual Assigned / Cascaded Targets)
        if (!Schema::hasTable('spms_ipcr_targets')) {
            Schema::create('spms_ipcr_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ipcr_id');
                $table->unsignedBigInteger('opcr_target_id')->nullable(); // Foreign reference if cascaded
                $table->string('category')->default('Core Functions');
                $table->string('subcategory')->nullable();
                $table->text('mfo_pap');
                $table->text('success_indicators');
                $table->text('actual_accomplishment')->nullable();
                $table->decimal('rating_q', 3, 2)->nullable();
                $table->decimal('rating_e', 3, 2)->nullable();
                $table->decimal('rating_t', 3, 2)->nullable();
                $table->decimal('rating_ave', 4, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('assigned_by')->nullable(); // Office Head Employee ID
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spms_ipcr_targets');
        Schema::dropIfExists('spms_ipcrs');
        Schema::dropIfExists('spms_opcr_targets');
        Schema::dropIfExists('spms_opcrs');
    }
};
