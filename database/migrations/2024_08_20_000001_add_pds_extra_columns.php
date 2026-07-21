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
        // 12. learning_devs
        if (Schema::hasTable('learning_devs')) {
            Schema::table('learning_devs', function (Blueprint $table) {
                if (!Schema::hasColumn('learning_devs', 'learning_dev')) $table->text('learning_dev')->nullable();
                if (!Schema::hasColumn('learning_devs', 'inc_date1')) $table->string('inc_date1')->nullable();
                if (!Schema::hasColumn('learning_devs', 'inc_date2')) $table->string('inc_date2')->nullable();
                if (!Schema::hasColumn('learning_devs', 'num_hours')) $table->string('num_hours')->nullable();
                if (!Schema::hasColumn('learning_devs', 'types')) $table->string('types')->nullable();
                if (!Schema::hasColumn('learning_devs', 'conducted')) $table->string('conducted')->nullable();
                if (!Schema::hasColumn('learning_devs', 'attachment')) $table->string('attachment')->nullable();
                if (!Schema::hasColumn('learning_devs', 'status')) $table->string('status')->nullable();
                if (!Schema::hasColumn('learning_devs', 'remarks')) $table->text('remarks')->nullable();
            });
        }

        // 13. voluntary_works
        if (Schema::hasTable('voluntary_works')) {
            Schema::table('voluntary_works', function (Blueprint $table) {
                if (!Schema::hasColumn('voluntary_works', 'org_name')) $table->text('org_name')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'inc_date1')) $table->string('inc_date1')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'inc_date2')) $table->string('inc_date2')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'num_hours')) $table->string('num_hours')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'position')) $table->string('position')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'attachment')) $table->string('attachment')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'status')) $table->string('status')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'remarks')) $table->text('remarks')->nullable();
            });
        }

        // 14. work_experiences
        if (Schema::hasTable('work_experiences')) {
            Schema::table('work_experiences', function (Blueprint $table) {
                if (!Schema::hasColumn('work_experiences', 'inc_date1')) $table->string('inc_date1')->nullable();
                if (!Schema::hasColumn('work_experiences', 'inc_date2')) $table->string('inc_date2')->nullable();
                if (!Schema::hasColumn('work_experiences', 'department')) $table->string('department')->nullable();
                if (!Schema::hasColumn('work_experiences', 'salary')) $table->string('salary')->nullable();
                if (!Schema::hasColumn('work_experiences', 'sg_grade')) $table->string('sg_grade')->nullable();
                if (!Schema::hasColumn('work_experiences', 'attachment')) $table->string('attachment')->nullable();
                if (!Schema::hasColumn('work_experiences', 'stat_app')) $table->string('stat_app')->nullable();
                if (!Schema::hasColumn('work_experiences', 'status')) $table->string('status')->nullable();
                if (!Schema::hasColumn('work_experiences', 'service')) $table->string('service')->nullable();
                if (!Schema::hasColumn('work_experiences', 'supervisor')) $table->string('supervisor')->nullable();
                if (!Schema::hasColumn('work_experiences', 'list_accom')) $table->text('list_accom')->nullable();
                if (!Schema::hasColumn('work_experiences', 'actual_summary')) $table->text('actual_summary')->nullable();
                if (!Schema::hasColumn('work_experiences', 'remarks')) $table->text('remarks')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
