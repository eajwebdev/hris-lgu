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
        if (Schema::hasTable('leave_applications')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_applications', 'history')) {
                    $table->integer('history')->default(1);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leave_applications')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                if (Schema::hasColumn('leave_applications', 'history')) {
                    $table->dropColumn('history');
                }
            });
        }
    }
};
