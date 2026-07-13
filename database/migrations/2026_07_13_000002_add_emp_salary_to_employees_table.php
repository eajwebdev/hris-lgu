<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * emp_salary used to live on the mirrored employee row in the PMS database.
 * With a single database, it belongs on the employee record itself.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('employees', 'emp_salary')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->decimal('emp_salary', 12, 2)->default(0)->after('emp_status');
            });
        }
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('emp_salary');
        });
    }
};
