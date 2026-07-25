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
        Schema::table('spms_opcr_items', function (Blueprint $table) {
            if (!Schema::hasColumn('spms_opcr_items', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
        });

        Schema::table('spms_ipcr_items', function (Blueprint $table) {
            if (!Schema::hasColumn('spms_ipcr_items', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spms_opcr_items', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('spms_ipcr_items', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
