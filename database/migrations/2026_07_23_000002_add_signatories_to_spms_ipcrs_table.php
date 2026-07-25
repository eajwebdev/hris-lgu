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
        Schema::table('spms_ipcrs', function (Blueprint $table) {
            if (!Schema::hasColumn('spms_ipcrs', 'ratee_name')) {
                $table->string('ratee_name')->nullable();
                $table->string('ratee_position')->nullable();
                $table->string('assessed_by_name')->nullable();
                $table->string('assessed_by_position')->nullable();
                $table->string('approved_by_name')->nullable();
                $table->string('approved_by_position')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spms_ipcrs', function (Blueprint $table) {
            $table->dropColumn([
                'ratee_name',
                'ratee_position',
                'assessed_by_name',
                'assessed_by_position',
                'approved_by_name',
                'approved_by_position',
            ]);
        });
    }
};
