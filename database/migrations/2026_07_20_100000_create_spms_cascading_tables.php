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
        // 1. OPCR Form Table
        if (!Schema::hasTable('spms_opcrs')) {
            Schema::create('spms_opcrs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('office_id');
                $table->unsignedBigInteger('office_head_id')->nullable();
                $table->integer('year')->default(2026);
                $table->tinyInteger('semester')->default(1);
                $table->string('status')->default('Draft');
                $table->decimal('total_core_score', 5, 3)->nullable();
                $table->decimal('total_support_score', 5, 3)->nullable();
                $table->decimal('final_numerical_rating', 4, 2)->nullable();
                $table->string('final_adjectival_rating', 10)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // 2. OPCR Items Table (Individual Objectives in OPCR Matrix)
        if (!Schema::hasTable('spms_opcr_items')) {
            Schema::create('spms_opcr_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('opcr_id');
                $table->string('category')->default('Core Functions');
                $table->string('subcategory')->nullable();
                $table->text('mfo_pap');
                $table->text('success_indicators');
                $table->string('allotted_budget')->nullable();
                $table->string('division_accountable')->nullable();
                $table->text('actual_accomplishment')->nullable();
                $table->decimal('rating_q', 3, 2)->nullable();
                $table->decimal('rating_e', 3, 2)->nullable();
                $table->decimal('rating_t', 3, 2)->nullable();
                $table->decimal('rating_ave', 4, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // 3. IPCR Form Table
        if (!Schema::hasTable('spms_ipcrs')) {
            Schema::create('spms_ipcrs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('office_id');
                $table->unsignedBigInteger('opcr_id')->nullable();
                $table->integer('year')->default(2026);
                $table->tinyInteger('semester')->default(1);
                $table->string('status')->default('Draft');
                $table->decimal('total_core_score', 5, 3)->nullable();
                $table->decimal('total_support_score', 5, 3)->nullable();
                $table->decimal('final_numerical_rating', 4, 2)->nullable();
                $table->string('final_adjectival_rating', 10)->nullable();
                $table->text('comments_recommendations')->nullable();
                $table->timestamps();
            });
        }

        // 4. IPCR Items Table (Traceable row-level cascaded objectives)
        if (!Schema::hasTable('spms_ipcr_items')) {
            Schema::create('spms_ipcr_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ipcr_id');
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('opcr_item_id')->nullable(); // Parent OPCR item reference
                $table->unsignedBigInteger('assigned_by')->nullable(); // Office Head employee ID
                $table->string('category')->default('Core Functions');
                $table->string('subcategory')->nullable();
                $table->text('mfo_pap');
                $table->text('success_indicators');
                $table->text('actual_accomplishment')->nullable();
                $table->string('evidence_file')->nullable(); // Uploaded document
                $table->decimal('rating_q', 3, 2)->nullable();
                $table->decimal('rating_e', 3, 2)->nullable();
                $table->decimal('rating_t', 3, 2)->nullable();
                $table->decimal('rating_ave', 4, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->string('status')->default('Assigned'); // Assigned, Submitted, Evaluated
                $table->timestamps();

                // Unique constraint to prevent duplicate assignment of same OPCR item to same employee
                $table->unique(['opcr_item_id', 'employee_id'], 'unique_opcr_item_employee');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spms_ipcr_items');
        Schema::dropIfExists('spms_ipcrs');
        Schema::dropIfExists('spms_opcr_items');
        Schema::dropIfExists('spms_opcrs');
    }
};
