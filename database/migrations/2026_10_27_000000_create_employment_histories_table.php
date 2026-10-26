<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each period an employee has worked for the LGU, one row apiece: the
 * position, office and status they held, from when to when, and why it
 * ended. The row with no date_end is the period they are in now; an employee
 * with none is separated. See App\Models\EmploymentHistory.
 *
 * Every employee already on record is given their current period, built
 * from what their record says today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->unsignedBigInteger('office_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('item_no')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->string('separation_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date_end']);
        });

        DB::table('employment_histories')->insertUsing(
            ['employee_id', 'position', 'office_id', 'status_id', 'item_no', 'date_start', 'created_at', 'updated_at'],
            DB::table('employees')->select([
                'id', 'position',
                DB::raw("NULLIF(emp_dept, '')"),
                DB::raw("NULLIF(emp_status, '')"),
                'item_no', 'date_hired',
                DB::raw('CURRENT_TIMESTAMP'), DB::raw('CURRENT_TIMESTAMP'),
            ])
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_histories');
    }
};
