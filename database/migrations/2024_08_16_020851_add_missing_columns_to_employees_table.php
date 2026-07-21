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
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'supervisor')) {
                $table->string('supervisor')->nullable();
            }
            if (!Schema::hasColumn('employees', 'dpn')) {
                $table->integer('dpn')->default(0);
            }
            if (!Schema::hasColumn('employees', 'vl')) {
                $table->decimal('vl', 8, 2)->default(15);
            }
            if (!Schema::hasColumn('employees', 'sl')) {
                $table->decimal('sl', 8, 2)->default(15);
            }
            if (!Schema::hasColumn('employees', 'mat_leave')) {
                $table->decimal('mat_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'special_pl')) {
                $table->decimal('special_pl', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'solo_pl')) {
                $table->decimal('solo_pl', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'study_leave')) {
                $table->decimal('study_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'vawc_leave')) {
                $table->decimal('vawc_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'rehab_leave')) {
                $table->decimal('rehab_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'benefits_leave')) {
                $table->decimal('benefits_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'calamity_leave')) {
                $table->decimal('calamity_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'adopt_leave')) {
                $table->decimal('adopt_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'servcred_leave')) {
                $table->decimal('servcred_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'well_leave')) {
                $table->decimal('well_leave', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'area_id')) {
                $table->string('area_id')->nullable();
            }
            if (!Schema::hasColumn('employees', 'android_id')) {
                $table->string('android_id')->nullable();
            }
            if (!Schema::hasColumn('employees', 'verification_code')) {
                $table->string('verification_code')->nullable();
            }
            if (!Schema::hasColumn('employees', 'height_m')) {
                $table->float('height_m')->nullable();
            }
            if (!Schema::hasColumn('employees', 'c_category')) {
                $table->string('c_category')->nullable();
            }
            if (!Schema::hasColumn('employees', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('employees', 'esign')) {
                $table->string('esign')->nullable();
            }
            if (!Schema::hasColumn('employees', 'strat_function')) {
                $table->string('strat_function')->nullable();
            }
            if (!Schema::hasColumn('employees', 'face_embeddings')) {
                $table->json('face_embeddings')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'supervisor', 'dpn', 'vl', 'sl', 'mat_leave', 'special_pl', 'solo_pl',
                'study_leave', 'vawc_leave', 'rehab_leave', 'benefits_leave', 'calamity_leave',
                'adopt_leave', 'servcred_leave', 'well_leave', 'area_id', 'android_id',
                'verification_code', 'height_m', 'c_category', 'country', 'esign',
                'strat_function', 'face_embeddings'
            ]);
        });
    }
};
