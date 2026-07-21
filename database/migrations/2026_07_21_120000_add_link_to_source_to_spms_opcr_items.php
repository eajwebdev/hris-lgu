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
        if (Schema::hasTable('spms_opcr_items') && !Schema::hasColumn('spms_opcr_items', 'link_to_source')) {
            Schema::table('spms_opcr_items', function (Blueprint $table) {
                $table->string('link_to_source')->nullable()->after('success_indicators');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('spms_opcr_items') && Schema::hasColumn('spms_opcr_items', 'link_to_source')) {
            Schema::table('spms_opcr_items', function (Blueprint $table) {
                $table->dropColumn('link_to_source');
            });
        }
    }
};
