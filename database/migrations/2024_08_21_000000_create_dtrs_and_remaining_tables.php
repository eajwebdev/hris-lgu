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
        // 1. dtrs
        if (!Schema::hasTable('dtrs')) {
            Schema::create('dtrs', function (Blueprint $table) {
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
                $table->string('under_time')->nullable();
                $table->string('tardiness')->nullable();
            });
        }

        // 2. f_devices
        if (!Schema::hasTable('f_devices')) {
            Schema::create('f_devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_id')->nullable();
                $table->unsignedBigInteger('camp_id')->nullable();
                $table->string('label')->nullable();
                $table->timestamps();
            });
        }

        // 3. events
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('venue')->nullable();
                $table->dateTime('start')->nullable();
                $table->dateTime('end')->nullable();
                $table->string('emp_status')->nullable();
                $table->string('bg_color')->nullable();
                $table->string('org_dept')->nullable();
                $table->string('remember_token')->nullable();
                $table->string('event_stat')->nullable();
                $table->timestamps();
            });
        }

        // 4. event_logs
        if (!Schema::hasTable('event_logs')) {
            Schema::create('event_logs', function (Blueprint $table) {
                $table->id();
                $table->string('empid')->nullable();
                $table->unsignedBigInteger('event_id')->nullable();
                $table->dateTime('in')->nullable();
                $table->dateTime('out')->nullable();
                $table->timestamps();
            });
        }

        // 5. ete_applicant_ratings
        if (!Schema::hasTable('ete_applicant_ratings')) {
            Schema::create('ete_applicant_ratings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ete_id')->nullable();
                $table->unsignedBigInteger('application_id')->nullable();
                $table->unsignedBigInteger('jid')->nullable();
                $table->date('evaluation_date')->nullable();
                $table->string('present_position')->nullable();
                $table->string('college_department')->nullable();
                $table->boolean('education_met')->default(false);
                $table->boolean('experience_met')->default(false);
                $table->boolean('eligibility_met')->default(false);
                $table->boolean('training_met')->default(false);
                $table->decimal('minimum_requirement_score', 8, 2)->default(0);
                $table->decimal('education_score', 8, 2)->default(0);
                $table->json('education_ratings')->nullable();
                $table->decimal('training_score', 8, 2)->default(0);
                $table->json('training_ratings')->nullable();
                $table->decimal('experience_score', 8, 2)->default(0);
                $table->json('experience_year_ratings')->nullable();
                $table->decimal('total_score', 8, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // 6. regions, provinces, cities, barangays
        if (!Schema::hasTable('regions')) {
            Schema::create('regions', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name')->nullable();
                $table->string('region_id')->nullable();
            });
        }

        if (!Schema::hasTable('provinces')) {
            Schema::create('provinces', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name')->nullable();
                $table->string('region_id')->nullable();
                $table->string('province_id')->nullable();
            });
        }

        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name')->nullable();
                $table->string('region_id')->nullable();
                $table->string('province_id')->nullable();
                $table->string('city_id')->nullable();
            });
        }

        if (!Schema::hasTable('barangays')) {
            Schema::create('barangays', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name')->nullable();
                $table->string('region_id')->nullable();
                $table->string('province_id')->nullable();
                $table->string('city_id')->nullable();
            });
        }

        // 7. dtr_tests
        if (Schema::hasTable('dtr_tests')) {
            Schema::table('dtr_tests', function (Blueprint $table) {
                if (!Schema::hasColumn('dtr_tests', 'emp_ID')) $table->string('emp_ID')->nullable();
                if (!Schema::hasColumn('dtr_tests', 'date')) $table->date('date')->nullable();
                if (!Schema::hasColumn('dtr_tests', 'time_in')) $table->string('time_in')->nullable();
                if (!Schema::hasColumn('dtr_tests', 'time_out')) $table->string('time_out')->nullable();
                if (!Schema::hasColumn('dtr_tests', 'time_over')) $table->string('time_over')->nullable();
                if (!Schema::hasColumn('dtr_tests', 'device_id')) $table->string('device_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangays');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('ete_applicant_ratings');
        Schema::dropIfExists('event_logs');
        Schema::dropIfExists('events');
        Schema::dropIfExists('f_devices');
        Schema::dropIfExists('dtrs');
    }
};
